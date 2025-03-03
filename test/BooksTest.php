<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class BooksTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        $this->conn = new \mysqli('localhost', 'root', '', 'capstone');

        if ($this->conn->connect_error) {
            $this->fail('Database connection failed: ' . $this->conn->connect_error);
        }
    }

    public function testGetAllBooks()
    {
        $result = $this->conn->query("SELECT * FROM books WHERE Stock > 0");

        if ($result === false) {
            $this->fail('Query failed: ' . $this->conn->error);
        }

        $this->assertGreaterThan(0, $result->num_rows);
    }

    public function testFilterBooksByLanguage()
    {
        $language = 'English';

        $stmt = $this->conn->prepare("SELECT * FROM books WHERE Language = ? AND Stock > 0");
        $stmt->bind_param('s', $language);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result === false) {
            $this->fail('Query failed: ' . $this->conn->error);
        }

        while ($book = $result->fetch_assoc()) {
            $this->assertEquals($book['Language'], $language);
        }
    }

    public function AnotherTester() {}

    protected function tearDown(): void
    {
        $this->conn->close();
    }
}
