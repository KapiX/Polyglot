<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Language;
use App\Models\GlossaryEntry;

class GlossaryEntryControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    private $languages;
    private $from;
    private $route;
    private $to;

    private function assertSuccess($response)
    {
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();
        $response->assertRedirect($this->to);
    }

    private function assertErrors($response, $errors)
    {
        $response->assertSessionHasErrors($errors);
        $response->assertRedirect($this->from);
    }

    private function storeRequest($user, $input)
    {
        return $this->from($this->from)->actingAs($user)
            ->post($this->route, $input);
    }

    public function setUp() : void
    {
        parent::setUp();

        $names = ['lang-1', 'lang-2'];
        $this->languages = [];
        foreach($names as $name) {
            $language = new Language;
            $language->name = $name;
            $language->iso_code = $name;
            $language->save();
            $this->languages[] = $language;
        }

        $this->from = route('glossaries.entries.create', [$this->languages[0]->id]);
        $this->route = route('glossaries.entries.store', [$this->languages[0]->id]);
        $this->to = route('glossaries.entries.index', [$this->languages[0]->id]);
    }

    public function testEmpty()
    {
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => '',
            'translation' => ''
        ]);

        $this->assertErrors($response, ['text', 'translation']);
    }

    public function testSingleLine()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $this->assertDatabaseCount('glossary', 1);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $this->assertSuccess($response);
    }

    public function testRegularUser()
    {
        $user = User::factory()->create();
        $response = $this->storeRequest($user, [
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $this->assertDatabaseCount('glossary', 0);

        $response->assertForbidden();
    }

    public function testLanguageManager()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[0]]
        )->create();
        $response = $this->storeRequest($user, [
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $this->assertDatabaseCount('glossary', 1);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $this->assertSuccess($response);
    }

    public function testLanguageManagerForADifferentLanguage()
    {
        $user = User::factory()->hasAttached(
            [$this->languages[1]]
        )->create();
        $response = $this->storeRequest($user, [
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $this->assertDatabaseCount('glossary', 0);

        $response->assertForbidden();
    }

    public function testMultipleLines()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\ntest2\ntest3",
            'translation' => "test-translation\ntest-translation2\ntest-translation3"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithWindowsLineEndings()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\r\ntest2\r\ntest3",
            'translation' => "test-translation\r\ntest-translation2\r\ntest-translation3"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesPreservesWhitespace()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test  \n\ttest2\n   test3",
            'translation' => "test-translation\t\ntest-translation2  \n  test-translation3"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test  ',
            'translation' => "test-translation\t"
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => "\ttest2",
            'translation' => 'test-translation2  '
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => '   test3',
            'translation' => '  test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithTrailingNewline()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\ntest2\ntest3\n",
            'translation' => "test-translation\ntest-translation2\ntest-translation3\n"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithMultipleTrailingNewline()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\ntest2\ntest3\n\n\n",
            'translation' => "test-translation\ntest-translation2\ntest-translation3\n\n\n"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithEmptyLinesInTheMiddle()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\ntest2\n\ntest3",
            'translation' => "test-translation\ntest-translation2\n\ntest-translation3"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithSomeFieldsEmpty()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\n\ntest2\ntest3\n",
            'translation' => "test-translation\ntest-translation2\n\ntest-translation3"
        ]);

        $this->assertDatabaseCount('glossary', 2);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testSingleLineWithExistingDuplicate()
    {
        $user = User::factory()->admin()->create();
        GlossaryEntry::factory()->for($this->languages[0])->create([
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $response = $this->storeRequest($user, [
            'text' => 'test',
            'translation' => 'test-translation-different'
        ]);

        $this->assertErrors($response, ['text']);
    }

    public function testSingleLineWithExistingDuplicateInADifferentGlossary()
    {
        $user = User::factory()->admin()->create();
        GlossaryEntry::factory()->for($this->languages[1])->create([
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $response = $this->storeRequest($user, [
            'text' => 'test',
            'translation' => 'test-translation-different'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithExistingDuplicate()
    {
        $user = User::factory()->admin()->create();
        GlossaryEntry::factory()->for($this->languages[0])->create([
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);

        $response = $this->storeRequest($user, [
            'text' => "test\ntest2",
            'translation' => "test-translation-different\ntest-translation-different2"
        ]);

        $this->assertErrors($response, ['text']);
    }

    public function testMultipleLinesDifferentWhitespaceIsNotDuplicate()
    {
        $user = User::factory()->admin()->create();
        GlossaryEntry::factory()->for($this->languages[0])->create([
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);

        $response = $this->storeRequest($user, [
            'text' => "test\n  test2",
            'translation' => "test-translation-different\ntest-translation-different2"
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesDifferentCasingIsNotDuplicate()
    {
        $user = User::factory()->admin()->create();
        GlossaryEntry::factory()->for($this->languages[0])->create([
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);

        $response = $this->storeRequest($user, [
            'text' => "test\nTest2",
            'translation' => "test-translation-different\ntest-translation-different2"
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithDuplicatesInInput()
    {
        $user = User::factory()->admin()->create();

        $response = $this->storeRequest($user, [
            'text' => "test\ntest",
            'translation' => "test-translation-different\ntest-translation-different2"
        ]);

        $this->assertErrors($response, ['text.*']);
    }

    public function testMultipleLinesDifferentWhitespaceInInputIsNotDuplicate()
    {
        $user = User::factory()->admin()->create();

        $response = $this->storeRequest($user, [
            'text' => "test\n   test",
            'translation' => "test-translation-different\ntest-translation-different2"
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesDifferentCasingInInputIsNotDuplicate()
    {
        $user = User::factory()->admin()->create();

        $response = $this->storeRequest($user, [
            'text' => "test\nTest",
            'translation' => "test-translation-different\ntest-translation-different2"
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithMoreTexts()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\ntest2\ntest3\ntest4\ntest5",
            'translation' => "test-translation\ntest-translation2\ntest-translation3"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithMoreTranslations()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $response = $this->storeRequest($user, [
            'text' => "test\ntest2\ntest3",
            'translation' => "test-translation\ntest-translation2\ntest-translation3\ntest-translation4\ntest-translation5"
        ]);

        $this->assertDatabaseCount('glossary', 3);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test2',
            'translation' => 'test-translation2'
        ]);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test3',
            'translation' => 'test-translation3'
        ]);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithALotOfEntries()
    {
        $user = User::factory()->admin()->create();
        $texts = 'test';
        $translations = 'test-translation';
        for($i = 2; $i <= 500; ++$i) {
            $texts .= "\n" . 'test' . $i;
            $translations .= "\n" . 'test-translation' . $i;
        }
        $response = $this->storeRequest($user, [
            'text' => $texts,
            'translation' => $translations
        ]);

        $this->assertDatabaseCount('glossary', 500);

        $this->assertSuccess($response);
    }

    public function testMultipleLinesWithTooManyEntries()
    {
        $user = User::factory()->admin()->create();
        $texts = 'test';
        $translations = 'test-translation';
        for($i = 2; $i <= 2000; ++$i) {
            $texts .= "\n" . 'test' . $i;
            $translations .= "\n" . 'test-translation' . $i;
        }
        $response = $this->storeRequest($user, [
            'text' => $texts,
            'translation' => $translations
        ]);

        $this->assertDatabaseCount('glossary', 0);

        $this->assertErrors($response, ['text']);
    }
}
