<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\NotificationContentSanitizer;
use App\Shared\NotificationCriteria;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;

final class NotificationMigrationTest extends Unit
{
    public function testCriteriaReadsSimpleAndLegacyQueryNames(): void
    {
        $criteria = NotificationCriteria::fromQuery([
            'page' => '3',
            'content' => 'kelulusan',
            'NotificationSearch' => [
                'title' => 'Cuti',
                'status' => '1',
                'ntf_dt' => '2026-11-28',
            ],
        ]);

        assertSame(3, $criteria->page);
        assertSame('Cuti', $criteria->title);
        assertSame(1, $criteria->status);
        assertSame('kelulusan', $criteria->content);
        assertSame('2026-11-28', $criteria->date);
        assertSame(5, $criteria->pageSize);
    }

    public function testSanitizerKeepsUsefulLinksAndRemovesUnsafeMarkup(): void
    {
        $sanitizer = new NotificationContentSanitizer();

        $html = $sanitizer->toSafeHtml(
            '<p onclick="bad()">Sila <a href="/cuti/individu/list" style="color:red">KLIK</a></p>'
            . '<script>alert(1)</script><a href="javascript:alert(1)">bad</a>',
        );

        assertStringContainsString('<a href="/cuti/individu/list">KLIK</a>', $html);
        assertStringNotContainsString('onclick', $html);
        assertStringNotContainsString('style=', $html);
        assertStringNotContainsString('script', $html);
        assertStringNotContainsString('javascript:', $html);
    }

    public function testPlainTextExcerptSourceRemovesHtml(): void
    {
        $sanitizer = new NotificationContentSanitizer();

        assertSame('Cuti baru KLIK', $sanitizer->toPlainText('<b>Cuti baru</b> <a href="/x">KLIK</a>'));
    }
}
