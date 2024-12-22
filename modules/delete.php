<?php
class Delete {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Delete account
    public function deleteAccount($id) {
        try {
            $sql = "DELETE FROM accounts WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return ["success" => "Account deleted successfully"];
        } catch (PDOException $e) {
            return ["error" => "Error deleting account: " . $e->getMessage()];
        }
    }

    // Delete transaction
    public function deleteTransaction($id) {
        try {
            $sql = "DELETE FROM transactions WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return ["success" => "Transaction deleted successfully"];
        } catch (PDOException $e) {
            return ["error" => "Error deleting transaction: " . $e->getMessage()];
        }
    }

    // Delete user
    public function deleteUser($id) {
        try {
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return ["success" => "User deleted successfully"];
        } catch (PDOException $e) {
            return ["error" => "Error deleting user: " . $e->getMessage()];
        }
    }
}

?>
