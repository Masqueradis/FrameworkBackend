<?php

namespace Tests\Unit;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderConfirmationMailTest extends TestCase
{
    public function testMailHasCorrectContentAndAttachments(): void
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
