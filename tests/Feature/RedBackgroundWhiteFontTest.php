<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedBackgroundWhiteFontTest extends TestCase
{
    public function test_style_css_enforces_white_font_on_all_red_backgrounds(): void
    {
        $cssPath = public_path('css/style.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // Verify .btn-danger has white font with !important
        $this->assertMatchesRegularExpression('/\.btn-danger[\s\S]*?color:\s*#ffffff\s*!important/i', $css);

        // Verify .badge-danger, .badge-rejected have white font with !important
        $this->assertMatchesRegularExpression('/\.badge-danger[\s\S]*?color:\s*#ffffff\s*!important/i', $css);
        $this->assertMatchesRegularExpression('/\.badge-rejected[\s\S]*?color:\s*#ffffff\s*!important/i', $css);

        // Verify global rule for [style*="background:#ef4444" i] and #dc2626
        $this->assertStringContainsString('[style*="background:#ef4444" i]', $css);
        $this->assertStringContainsString('[style*="background:#dc2626" i]', $css);
        $this->assertStringContainsString('[style*="background:var(--danger)" i]', $css);

        // Verify child selector * inheritance for red backgrounds
        $this->assertStringContainsString('[style*="background:#ef4444" i] *', $css);
        $this->assertStringContainsString('.btn-danger *', $css);
    }
}
