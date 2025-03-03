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
        $query = "SELECT * FROM webuser WHERE email = '$email' AND usertype = '$usertype'";
        $result = $this->conn->query($query);

        $this->assertNotFalse($result, 'Query failed.');
        $this->assertGreaterThan(0, $result->num_rows, 'User does not exist.');
    }

    public function testUserLoginWithValidCredentials()
    {
        $email = 'jems@gmail.com';
        $password = '123';

        $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
        $result = $this->conn->query($query);

        $this->assertNotFalse($result, 'Query failed.');
        $this->assertGreaterThan(0, $result->num_rows, 'Invalid ang credentials sa login');
    }

    // public function testUserLoginWithInvalidCredentials()
    // {
    //     $email = 'jemsyawa@gmail.com';
    //     $password = '123';

    //     $stmt = $this->conn->prepare("SELECT password FROM users WHERE email = ?");
    //     $stmt->bind_param("s", $email);
    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     if ($row = $result->fetch_assoc()) {
    //         $storedPassword = $row['password'];

    //         // Use password_verify only if passwords are hashed
    //         if (password_get_info($storedPassword)['algo'] !== 0) {
    //             $isValid = password_verify($password, $storedPassword);
    //         } else {
    //             $isValid = ($password === $storedPassword);
    //         }
    //     } else {
    //         $isValid = false;
    //     }

    //     $this->assertFalse($isValid, 'Dili kasulod dapat kung mali iya gibutang na password or email');
    //     $stmt->close();
    // }





    protected function tearDown(): void
    {
        $this->conn->close();
    }
}
