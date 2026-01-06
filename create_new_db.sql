-- Create new M-Pesa database
CREATE DATABASE IF NOT EXISTS mpesa_transactions;
USE mpesa_transactions;

-- Create transactions table with proper field lengths
CREATE TABLE transactions (
    ID int(11) NOT NULL AUTO_INCREMENT,
    MerchantRequestID varchar(100) NOT NULL,
    CheckoutRequestID varchar(100) NOT NULL,
    ResultCode int(11) NOT NULL,
    ResultDesc varchar(255) DEFAULT NULL,
    Amount decimal(10,2) NOT NULL,
    MpesaReceiptNumber varchar(50) NOT NULL,
    PhoneNumber varchar(15) NOT NULL,
    TransactionDate bigint(20) DEFAULT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY unique_checkout (CheckoutRequestID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
