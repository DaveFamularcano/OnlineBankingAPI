<?php
class Authentication {
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Check if the user is authorized by comparing token in headers
    public function isAuthorized() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        if (isset($headers['authorization'])) {
            return $this->getToken() === $headers['authorization'];
        }
        return false;
    }

    private function getToken() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        if (isset($headers['x-auth-user'])) {
            $sqlString = "SELECT token FROM users WHERE username=?";
            try {
                $stmt = $this->pdo->prepare($sqlString);
                $stmt->execute([$headers['x-auth-user']]);
                $result = $stmt->fetch();
                return $result ? $result['token'] : '';
            } catch (Exception $e) {
                return "";
            }
        }
        return "";
    }

    private function generateHeader() {
        $header = ["typ" => "JWT", "alg" => "HS256", "app" => "Banking System"];
        return base64_encode(json_encode($header));
    }

    private function generatePayload($id, $username) {
        $payload = ["user_id" => $id, "user_cred" => $username];
        return base64_encode(json_encode($payload));
    }

    private function generateToken($id, $username) {
        $header = $this->generateHeader();
        $payload = $this->generatePayload($id, $username);
        $signature = base64_encode(hash_hmac("sha256", "$header.$payload", "your_secret_key", true));
        return "$header.$payload.$signature";
    }

    public function saveToken($token, $username) {
        try {
            $sqlString = "UPDATE users SET token=? WHERE username=?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute([$token, $username]);
            return ["data" => null, "code" => 200];
        } catch (\PDOException $e) {
            return ["errmsg" => $e->getMessage(), "code" => 400];
        }
    }

    public function login($body) {
        $username = $body['username'] ?? null;
        $password = $body['password'] ?? null;

        if (empty($username) || empty($password)) {
            throw new \Exception("Username and password are required.");
        }

        $code = 0;
        $payload = "";
        $remarks = "";
        $message = "";

        try {
            $sqlString = "SELECT id, username, password FROM users WHERE username=?";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch();

                if (password_verify($password, $result['password'])) {
                    $code = 200;
                    $remarks = "Success";
                    $message = "Logged in successfully";

                    $token = $this->generateToken($result['id'], $result['username']);
                    $token_arr = explode('.', $token);

                    $this->saveToken($token_arr[2], $result['username']);

                    $payload = ["id" => $result['id'], "username" => $result['username'], "token" => $token_arr[2]];
                } else {
                    $code = 401;
                    $payload = null;
                    $remarks = "Failed";
                    $message = "Incorrect Password.";
                }
            } else {
                $code = 401;
                $payload = null;
                $remarks = "Failed";
                $message = "Username does not exist.";
            }
        } catch (\PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $remarks = "Failed";
            $code = 400;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $remarks = "Failed";
            $code = 400;
        }

        return ["payload" => $payload, "remarks" => $remarks, "message" => $message, "code" => $code];
    }

    // Add a new user and create an account for them
    public function addUser($body) {
        $values = [];
        $errmsg = "";
        $code = 0;

        // Hash password
        $password = isset($body["password"]) ? $this->encryptPassword($body["password"]) : '';

        foreach ($body as $key => $value) {
            if ($key != 'password') { 
                array_push($values, $value);
            }
        }

        // Add password to values
        array_push($values, $password);

        try {
            // Start a transaction to ensure both the user and account are created successfully
            $this->pdo->beginTransaction();

            // Insert user into users table
            $sqlString = "INSERT INTO users (id, username, password) VALUES (NULL, ?, ?)";
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
}
?>
