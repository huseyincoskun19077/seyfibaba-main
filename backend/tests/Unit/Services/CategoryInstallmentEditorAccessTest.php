<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\API\CategoryInstallmentEditorController;
use Tests\TestCase;

class CategoryInstallmentEditorAccessTest extends TestCase
{
    public function test_only_the_iyzico_test_email_is_allowed(): void
    {
        $this->assertTrue(CategoryInstallmentEditorController::isAllowedEmail('iyzicotestekibi@gmail.com'));
        $this->assertTrue(CategoryInstallmentEditorController::isAllowedEmail('  IyzicoTestEkibi@gmail.com '));
        $this->assertFalse(CategoryInstallmentEditorController::isAllowedEmail('baska@gmail.com'));
        $this->assertFalse(CategoryInstallmentEditorController::isAllowedEmail(''));
        $this->assertFalse(CategoryInstallmentEditorController::isAllowedEmail(null));
    }
}
