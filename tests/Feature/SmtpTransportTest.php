<?php

namespace Tests\Feature;

use App\Mail\AccountLink;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SmtpTransportTest extends TestCase
{
    public function test_account_link_travels_through_actual_local_smtp_transport(): void
    {
        $server = new Process([PHP_BINARY, base_path('tests/Fixtures/smtp-server.php')]);
        $server->setTimeout(20);
        $server->start();
        try {
            $this->assertTrue($server->waitUntil(fn (string $type, string $output): bool => preg_match('/127\.0\.0\.1:\d+/', $server->getOutput()) === 1));
            preg_match('/127\.0\.0\.1:(\d+)/', $server->getOutput(), $matches);
            config(['mail.mailers.integration' => ['transport' => 'smtp', 'scheme' => 'smtp', 'host' => '127.0.0.1', 'port' => (int) $matches[1], 'timeout' => 5],
                'mail.from.address' => 'sender@example.test', 'mail.from.name' => 'Modelo test']);
            Mail::mailer('integration')->to('destination@example.test')->send(new AccountLink('Verifica tu correo', 'http://localhost/verificar/test-token'));
            Mail::purge('integration');
            $server->wait();
            $this->assertTrue($server->isSuccessful(), $server->getErrorOutput());
            $this->assertStringContainsString('LINK_RECEIVED', $server->getOutput());
        } finally {
            $server->stop();
        }
    }
}
