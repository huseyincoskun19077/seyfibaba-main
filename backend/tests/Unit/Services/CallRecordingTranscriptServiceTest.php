<?php

namespace Tests\Unit\Services;

use App\Models\CallRecording;
use App\Services\CallRecordingTranscriptService;
use Tests\TestCase;

class CallRecordingTranscriptServiceTest extends TestCase
{
    public function test_first_voice_is_labeled_as_our_speaker_and_the_other_as_the_other_party(): void
    {
        $recording = new CallRecording([
            'source' => '101',
            'destination' => '05320000001',
        ]);

        $dialogue = (new CallRecordingTranscriptService)->buildDialogue($recording, [
            ['speaker' => 'A', 'text' => 'Merhaba, Kuaför Tedarik.', 'start' => 0, 'end' => 2],
            ['speaker' => 'A', 'text' => 'Kampanya bakar mısınız?', 'start' => 2, 'end' => 4],
            ['speaker' => 'B', 'text' => 'Fiyat isteyeceğim.', 'start' => 4, 'end' => 6],
        ]);

        $this->assertSame('Bizim konuşmacı (101)', $dialogue['our_label']);
        $this->assertSame('Karşı taraf konuşmacısı (05320000001)', $dialogue['other_label']);
        $this->assertCount(2, $dialogue['turns']);
        $this->assertSame('ours', $dialogue['turns'][0]['role']);
        $this->assertSame('Merhaba, Kuaför Tedarik. Kampanya bakar mısınız?', $dialogue['turns'][0]['text']);
        $this->assertSame('other', $dialogue['turns'][1]['role']);
        $this->assertSame('Fiyat isteyeceğim.', $dialogue['turns'][1]['text']);
    }

    public function test_prelabeled_roles_are_kept_when_the_other_party_speaks_first(): void
    {
        $recording = new CallRecording([
            'source' => '05320000002',
            'destination' => '101',
        ]);

        $dialogue = (new CallRecordingTranscriptService)->buildDialogue($recording, [
            ['speaker' => 'other', 'text' => 'Alo'],
            ['speaker' => 'ours', 'text' => 'Buyurun'],
        ], 'groq-split');

        $this->assertSame('other', $dialogue['turns'][0]['role']);
        $this->assertSame('ours', $dialogue['turns'][1]['role']);
        $this->assertSame('Bizim konuşmacı (101)', $dialogue['our_label']);
        $this->assertSame('Karşı taraf konuşmacısı (05320000002)', $dialogue['other_label']);
    }

    public function test_single_speaker_is_not_assigned_to_the_customer(): void
    {
        $recording = new CallRecording([
            'source' => '101',
            'destination' => '05320000003',
        ]);

        $dialogue = (new CallRecordingTranscriptService)->buildDialogue($recording, [
            ['speaker' => 'A', 'text' => 'Ses yok gibi, sonra anlatırım.'],
        ]);

        $this->assertSame('single', $dialogue['turns'][0]['role']);
    }
}
