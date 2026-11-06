<?php

namespace Tests\Feature;

use App\Services\SentimentAnalysisService;
use App\Support\AnalysisLimits;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalysisBatchingTest extends TestCase
{
    private function fakeDiagnosis(): void
    {
        Http::fake(function ($request) {
            return Http::response(['feedback_category_counts' => ['waiting_time' => 0], 'complaint_category_counts' => ['waiting_time' => 0],
                'details' => array_map(fn ($item) => ['id' => $item['id'], 'source' => $item['source'],
                    'matched_categories' => ['waiting_time'], 'findings' => [['evidence' => $item['text']]]], $request['items'])]);
        });
    }

    public function test_large_job_is_batched_and_every_record_and_count_is_preserved(): void
    {
        $this->fakeDiagnosis();
        $items = [];
        for ($i = 0; $i < 123; $i++) $items[] = ['id' => intdiv($i, 2) + 1, 'source' => $i % 2 ? 'complaint' : 'feedback', 'text' => 'Matagal ang pila'];
        $result = app(SentimentAnalysisService::class)->diagnose($items);
        Http::assertSentCount(3);
        $this->assertCount(123, $result['details']);
        $this->assertSame(62, $result['feedback_category_counts']['waiting_time']);
        $this->assertSame(61, $result['complaint_category_counts']['waiting_time']);
        foreach ($items as $i => $item) {
            $this->assertSame($item['id'], $result['details'][$i]['id']);
            $this->assertSame($item['source'], $result['details'][$i]['source']);
            $this->assertSame($item['text'], $result['details'][$i]['findings'][0]['evidence']);
        }
        foreach (Http::recorded() as [$request]) {
            $this->assertLessThanOrEqual(AnalysisLimits::MAX_BATCH_ITEMS, count($request['items']));
            $this->assertLessThanOrEqual(AnalysisLimits::MAX_REQUEST_BYTES, strlen($request->body()));
        }
    }

    public function test_batches_also_split_on_encoded_byte_size_before_the_item_limit(): void
    {
        $this->fakeDiagnosis();
        $text = str_repeat('😀', AnalysisLimits::MAX_TEXT_LENGTH);
        $items = array_map(fn ($i) => ['id' => $i, 'source' => 'feedback', 'text' => $text], range(1, 20));
        $result = app(SentimentAnalysisService::class)->diagnose($items);
        $this->assertCount(20, $result['details']);
        Http::assertSentCount(2);
        foreach (Http::recorded() as [$request]) $this->assertLessThanOrEqual(AnalysisLimits::MAX_REQUEST_BYTES, strlen($request->body()));
        $this->assertSame($text, $result['details'][0]['findings'][0]['evidence']);
    }

    public function test_failed_later_batch_does_not_return_misleading_partial_results(): void
    {
        Http::fakeSequence()->push(['details' => array_map(fn ($i) => ['id' => $i, 'source' => 'feedback', 'matched_categories' => []], range(1, 50))])->push([], 503);
        $items = array_map(fn ($i) => ['id' => $i, 'source' => 'feedback', 'text' => 'A comment'], range(1, 51));
        $this->assertNull(app(SentimentAnalysisService::class)->diagnose($items));
        Http::assertSentCount(2);
    }

    public function test_oversized_record_is_not_truncated_or_silently_dropped(): void
    {
        Http::fake();
        $this->assertNull(app(SentimentAnalysisService::class)->diagnose([
            ['id' => 1, 'source' => 'feedback', 'text' => 'Valid'],
            ['id' => 2, 'source' => 'feedback', 'text' => str_repeat('a', AnalysisLimits::MAX_TEXT_LENGTH + 1)],
        ]));
        Http::assertNothingSent();
    }

    public function test_duplicate_identities_are_rejected_even_across_batch_boundaries(): void
    {
        Http::fake();
        $items = array_map(fn ($i) => ['id' => $i, 'source' => 'feedback', 'text' => 'A comment'], range(1, 51));
        $items[] = $items[0];
        $this->assertNull(app(SentimentAnalysisService::class)->diagnose($items));
        Http::assertNothingSent();
    }

    public function test_incomplete_or_duplicate_response_details_are_rejected(): void
    {
        $item = ['id' => 1, 'source' => 'feedback', 'text' => 'Valid'];
        Http::fake(['*' => Http::response(['details' => []])]);
        $this->assertNull(app(SentimentAnalysisService::class)->diagnose([$item]));
        Http::fake(['*' => Http::response(['details' => [
            ['id' => 1, 'source' => 'feedback', 'matched_categories' => []],
            ['id' => 1, 'source' => 'feedback', 'matched_categories' => []],
        ]])]);
        $this->assertNull(app(SentimentAnalysisService::class)->diagnose([$item, array_replace($item, ['id' => 2])]));
    }

    public function test_single_record_analysis_keeps_the_existing_request_and_response(): void
    {
        Http::fake(['*/api/predict/' => Http::response(['sentiment_label' => 'Positive', 'confidence_score' => 0.9]),
            '*/api/categorize/' => Http::response(['category' => 'waiting_time'])]);
        $service = app(SentimentAnalysisService::class);
        $this->assertSame('Positive', $service->analyze('Helpful staff')['sentiment_label']);
        $this->assertSame('waiting_time', $service->categorize('Matagal ang pila'));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => ($request->data()['feedback_text'] ?? null) === 'Helpful staff');
    }

    public function test_single_record_analysis_does_not_send_oversized_text(): void
    {
        Http::fake();
        $service = app(SentimentAnalysisService::class);
        $text = str_repeat('a', AnalysisLimits::MAX_TEXT_LENGTH + 1);
        $this->assertNull($service->analyze($text));
        $this->assertNull($service->categorize($text));
        Http::assertNothingSent();
    }

    public function test_oversized_new_reviews_and_complaints_receive_validation_errors(): void
    {
        Http::fake();
        $text = str_repeat('a', AnalysisLimits::MAX_TEXT_LENGTH + 1);
        $this->postJson('/api/feedback', ['transaction_code' => str_repeat('A', 20), 'star_rating' => 5, 'feedback_text' => $text])
            ->assertUnprocessable()->assertJsonValidationErrors('feedback_text');
        $this->postJson('/api/complaints', ['complaint_text' => $text])
            ->assertUnprocessable()->assertJsonValidationErrors('complaint_text');
        Http::assertNothingSent();
    }
}
