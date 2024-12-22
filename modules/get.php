<?php
class Get {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get account details (by account ID or all accounts)
    public function getAccount($id = null) {
        try {
            if ($id) {
                $sql = "SELECT * FROM accounts WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($account) {
                    return $account;
                } else {
                    return ["error" => "Account not found"];
                }
            } else {
                $sql = "SELECT * FROM accounts";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return ["error" => "Error fetching accounts: " . $e->getMessage()];
        }
    }
    public function getAllTransactions() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM transactions"); // Adjust table name as necessary
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $transactions;
        } catch (Exception $e) {
            return ["error" => "Failed to fetch transactions: " . $e->getMessage()];
        }
    }
    // Get transaction details (by transaction ID or all transactions)
    public function getTransaction($id = null) {
        try {
            if ($id) {
                $sql = "SELECT * FROM transactions WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($transaction) {
                    return $transaction;
                } else {
                    return ["error" => "Transaction not found"];
                }
            } else {
                $sql = "SELECT * FROM transactions";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return ["error" => "Error fetching transactions: " . $e->getMessage()];
        }
    }

    // Get user details (by user ID or all users)
    public function getUser($id = null) {
        try {
            if ($id) {
                $sql = "SELECT * FROM users WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    return $user;
                } else {
                    return ["error" => "User not found"];
                }
            } else {
                $sql = "SELECT * FROM users";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return ["error" => "Error fetching users: " . $e->getMessage()];
        }
    }
}

?>
