<?php
class Post {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Add a new user (Register)
    public function addUser($body) {
        $values = [];
        $errmsg = "";
        $code = 0;
    
        // Hash password
        $password = isset($body["password"]) ? $this->encryptPassword($body["password"]) : '';
    
        // Ensure the body contains 'username' and 'password'
        if (isset($body["username"]) && !empty($body["username"])) {
            $values[] = $body["username"]; // Add username to values
        }
    
        if (!empty($password)) {
            $values[] = $password; // Add hashed password to values
        }
    
        try {
            // Start a transaction to ensure both the user and account are created successfully
            $this->pdo->beginTransaction();
    
            // Insert user into users table
            $sqlString = "INSERT INTO users (username, password) VALUES (?, ?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);
    
            // Get the newly created user's ID (use lastInsertId)
            $userId = $this->pdo->lastInsertId();
    
            // Insert account for the user in the accounts table
            $sqlString = "INSERT INTO accounts (user_id, balance) VALUES (?, ?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute([$userId, 0]); // Start with a balance of 0
    
            // Commit transaction
            $this->pdo->commit();
    
            $code = 200;
            return ["data" => null, "code" => $code];
        } catch (\PDOException $e) {
            // Rollback if there's an error
            $this->pdo->rollBack();
            $errmsg = $e->getMessage();
            $code = 400;
        }
    
        return ["errmsg" => $errmsg, "code" => $code];
    }
    
    private function encryptPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    // Login a user
    public function login($body) {
        // Validate input data
        if (empty($body['username']) || empty($body['password'])) {
            return ["error" => "Missing required fields: username, password"];
        }

        try {
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':username' => $body['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($body['password'], $user['password'])) {
                // Generate token (for simplicity, using a random string; consider using JWT in production)
                $token = bin2hex(random_bytes(16));

                // Store the token in the database
                $sql = "UPDATE users SET token = :token WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':token' => $token, ':id' => $user['id']]);

                return ["success" => "Login successful", "user_id" => $user['id'], "token" => $token];
            } else {
                return ["error" => "Invalid credentials"];
            }
        } catch (PDOException $e) {
            return ["error" => "Error logging in: " . $e->getMessage()];
        }
    }

    // Check if the user is authorized using the token
    public function isAuthorized($authHeader) {
        if (!$authHeader) {
            return false;
        }

        // Extract token from Authorization header
        $matches = [];
        if (preg_match('/Bearer (.+)/', $authHeader, $matches)) {
            $token = $matches[1];

            // Validate token in the database
            $sql = "SELECT * FROM users WHERE token = :token";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return true;  // Token is valid
            }
        }

        return false;
    }

    // Create a new transaction
    public function createTransaction($account_id, $transaction_type, $amount, $recipient_account_id = null) {
        try {
            if (empty($account_id) || empty($transaction_type) || empty($amount)) {
                return ["error" => "Missing required fields: account_id, transaction_type, or amount."];
            }

            // Validate account existence
            $sql = "SELECT * FROM accounts WHERE id = :account_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':account_id' => $account_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                return ["error" => "Account not found"];
            }

            // Perform the transaction logic
            if ($transaction_type === "Transfer") {
                if (empty($recipient_account_id)) {
                    return ["error" => "Recipient account ID is required for transfers."];
                }

                // Validate recipient account existence
                $sql = "SELECT * FROM accounts WHERE id = :recipient_account_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':recipient_account_id' => $recipient_account_id]);
                $recipient_account = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$recipient_account) {
                    return ["error" => "Recipient account not found"];
                }

                // Deduct from sender and add to recipient
                $this->pdo->beginTransaction();
                $sql = "UPDATE accounts SET balance = balance - :amount WHERE id = :account_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':amount' => $amount, ':account_id' => $account_id]);

                $sql = "UPDATE accounts SET balance = balance + :amount WHERE id = :recipient_account_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':amount' => $amount, ':recipient_account_id' => $recipient_account_id]);

                $sql = "INSERT INTO transactions (account_id, transaction_type, amount) VALUES (:account_id, 'Transfer', :amount)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':account_id' => $account_id, ':amount' => $amount]);

                $this->pdo->commit();

                return ["success" => "Transfer completed successfully."];
            } else {
                // Handle Deposit/Withdrawal
                $operation = $transaction_type === "Deposit" ? "+" : "-";
                $sql = "UPDATE accounts SET balance = balance $operation :amount WHERE id = :account_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':amount' => $amount, ':account_id' => $account_id]);

                $sql = "INSERT INTO transactions (account_id, transaction_type, amount) VALUES (:account_id, :transaction_type, :amount)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':account_id' => $account_id, ':transaction_type' => $transaction_type, ':amount' => $amount]);

                return ["success" => "$transaction_type completed successfully."];
            }
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["error" => "Error processing transaction: " . $e->getMessage()];
        }
    }
}

