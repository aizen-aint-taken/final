<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use mysqli;

class LoginTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        $this->conn = new mysqli('localhost', 'root', '', 'capstone');

        if ($this->conn->connect_error) {
            $this->fail('Database connection failed: ' . $this->conn->connect_error);
        }
    }

    public function testUserExists()
    {
        $email = 'admin@gmail.com';
        $usertype = 'a';


        $stmt = $this->conn->prepare("SELECT * FROM webuser WHERE email = ? AND usertype = ?");
        $stmt->bind_param("ss", $email, $usertype);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertNotFalse($result, 'Query failed.');
        $this->assertGreaterThan(0, $result->num_rows, 'User does not exist.');

        $stmt->close();
    }

    public function testUserLoginWithValidCredentials()
    {
        $email = 'angel@gmail.com';
        $password = '123';  // Make sure this matches the original password before hashing

        $stmt = $this->conn->prepare("SELECT * FROM webuser WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $this->assertNotFalse($result, 'Query failed.');
        $this->assertGreaterThan(0, $result->num_rows, 'User not found');

        $user = $result->fetch_assoc();
        $this->assertTrue(password_verify($password, $user['password']), 'Invalid credentials sa login');

        $stmt->close();
    }

    protected function tearDown(): void
    {
        $this->conn->close();
    }
}
