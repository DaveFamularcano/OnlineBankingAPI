<?php
class Patch {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Update account balance
    public function updateAccount($data, $id) {
        try {
            $sql = "UPDATE accounts SET balance = :balance WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':balance' => $data['balance'],
                ':id' => $id
            ]);
            return ["success" => "Account updated successfully"];
        } catch (PDOException $e) {
            return ["error" => "Error updating account: " . $e->getMessage()];
        }
    }

    // Update transaction record
    public function updateTransaction($data, $id) {
        try {
            $sql = "UPDATE transactions SET amount = :amount WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':amount' => $data['amount'],
                ':id' => $id
            ]);
            return ["success" => "Transaction updated successfully"];
        } catch (PDOException $e) {
            return ["error" => "Error updating transaction: " . $e->getMessage()];
        }
    }
}

?>
