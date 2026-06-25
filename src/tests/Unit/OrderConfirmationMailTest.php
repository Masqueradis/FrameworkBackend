<?php

namespace Tests\Unit;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Tests\TestCase;

class OrderConfirmationMailTest extends TestCase
{
    public function test_mail_has_correct_content_and_attachments(): void
    {
        $order = new Order(['id' => 123]);

        $mail = new OrderConfirmationMail($order);

        $content = $mail->content();
        $this->assertEquals('emails.orders.confirmation', $content->view);

        $attachments = $mail->attachments();
        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}
