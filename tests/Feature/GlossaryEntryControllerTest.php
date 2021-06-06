<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Language;
use App\Models\GlossaryEntry;

class GlossaryEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    private $languages;

    public function setUp() : void
    {
        parent::setUp();

        $names = ['a-lang-1', 'b-lang-2', 'c-lang-3', 'a-lang-4'];
        $this->languages = [];
        foreach($names as $name) {
            $language = new Language;
            $language->name = $name;
            $language->iso_code = $name;
            $language->save();
            $this->languages[] = $language;
        }
    }

    public function testList()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('glossaries'));

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.list');

        $response->assertSeeTextInOrder([
            'a-lang-1', 'a-lang-4', 'b-lang-2', 'c-lang-3'
        ]);
        $response->assertSee('<td>0</td>', false);
    }

    public function testIndex()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $entries = GlossaryEntry::factory()->count(5)->for($language)->create();
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]));

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        for($i = 0; $i < 5; ++$i) {
            $response->assertSeeText($entries[$i]->text);
            $response->assertSeeText($entries[$i]->translation);
            $response->assertSee(
                'id="letter-' . strtolower($entries[$i]->text[0]) . '">', false);
        }
        $response->assertSeeInOrder([
            'Add',
            $entries[0]->text,
            $entries[0]->translation,
            'Edit', 'Delete'
        ]);
    }

    public function testIndexLotsOfEntries()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $entries = GlossaryEntry::factory()->count(1000)->for($language)->create();
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]));

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        $response->assertSeeInOrder([
            'Add', 'Edit', 'Delete'
        ]);
    }

    public function testIndexRegularUser()
    {
        $language = $this->languages[0];
        $user = User::factory()->create();
        $entries = GlossaryEntry::factory()->count(5)->for($language)->create();
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]));

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        for($i = 0; $i < 5; ++$i) {
            $response->assertSeeText($entries[$i]->text);
            $response->assertSeeText($entries[$i]->translation);
            $response->assertSee(
                'id="letter-' . strtolower($entries[$i]->text[0]) . '">', false);
        }
        $response->assertDontSee('Add</a>', false);
        $response->assertDontSee('Edit</a>', false);
        $response->assertDontSee('Delete</input>', false);
    }

    public function testIndexLanguageManagerForADifferentLanguage()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[1]]
        )->create();
        $entries = GlossaryEntry::factory()->count(5)->for($language)->create();
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]));

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        for($i = 0; $i < 5; ++$i) {
            $response->assertSeeText($entries[$i]->text);
            $response->assertSeeText($entries[$i]->translation);
            $response->assertSee(
                'id="letter-' . strtolower($entries[$i]->text[0]) . '">', false);
        }
        $response->assertDontSee('Add</a>', false);
        $response->assertDontSee('Edit</a>', false);
        $response->assertDontSee('Delete</input>', false);
    }

    public function testIndexLanguageManager()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[0]]
        )->create();
        $entries = GlossaryEntry::factory()->count(5)->for($language)->create();
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]));

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        for($i = 0; $i < 5; ++$i) {
            $response->assertSeeText($entries[$i]->text);
            $response->assertSeeText($entries[$i]->translation);
            $response->assertSee(
                'id="letter-' . strtolower($entries[$i]->text[0]) . '">', false);
        }
        $response->assertSeeInOrder([
            'Add',
            $entries[0]->text,
            $entries[0]->translation,
            'Edit', 'Delete'
        ]);
    }

    public function testIndexSearchText()
    {
        $language = $this->languages[0];
        $user = User::factory()->create();
        GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        GlossaryEntry::factory()->for($language)->create([
            'text' => 'hijk',
            'translation' => 'oprs'
        ]);
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]) . '?search=abc');

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        $response->assertSeeText('abcdefg');
        $response->assertSeeText('tuvwxyz');
        $response->assertDontSeeText('hijk');
        $response->assertDontSeeText('oprs');
    }

    public function testIndexSearchTranslation()
    {
        $language = $this->languages[0];
        $user = User::factory()->create();
        GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        GlossaryEntry::factory()->for($language)->create([
            'text' => 'hijk',
            'translation' => 'oprs'
        ]);
    
        $response = $this->actingAs($user)->get(
            route('glossaries.entries.index', [$language->id]) . '?search=vwx');

        $response->assertSuccessful();
        $response->assertViewIs('glossaries.index');

        $response->assertSeeText('abcdefg');
        $response->assertSeeText('tuvwxyz');
        $response->assertDontSeeText('hijk');
        $response->assertDontSeeText('oprs');
    }

    public function testUpdate()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        $response = $this->actingAs($user)->put(
            route('glossaries.entries.update', [$language->id, $entry->id]),
            [
                'text' => 'test',
                'translation' => 'test-translation'
            ]
        );

        $this->assertDatabaseCount('glossary', 1);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(
            route('glossaries.entries.index', [$language->id]));
    }

    public function testUpdateRegularUser()
    {
        $language = $this->languages[0];
        $user = User::factory()->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        $response = $this->actingAs($user)->put(
            route('glossaries.entries.update', [$language->id, $entry->id]),
            [
                'text' => 'test',
                'translation' => 'test-translation'
            ]
        );

        $this->assertDatabaseCount('glossary', 1);
        $this->assertDatabaseHas('glossary', [
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $response->assertForbidden();
    }

    public function testUpdateLanguageManager()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[0]]
        )->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        $response = $this->actingAs($user)->put(
            route('glossaries.entries.update', [$language->id, $entry->id]),
            [
                'text' => 'test',
                'translation' => 'test-translation'
            ]
        );

        $this->assertDatabaseCount('glossary', 1);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $user->id,
            'language_id' => $language->id,
            'text' => 'test',
            'translation' => 'test-translation'
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(
            route('glossaries.entries.index', [$language->id]));
    }

    public function testUpdateLanguageManagerForADifferentLanguage()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[1]]
        )->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        $author_id = $entry->author_id;
        $response = $this->actingAs($user)->put(
            route('glossaries.entries.update', [$language->id, $entry->id]),
            [
                'text' => 'test',
                'translation' => 'test-translation'
            ]
        );

        $this->assertDatabaseCount('glossary', 1);
        $this->assertDatabaseHas('glossary', [
            'author_id' => $author_id,
            'language_id' => $language->id,
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $response->assertForbidden();
    }

    public function testUpdateDuplicatedText()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        GlossaryEntry::factory()->for($language)->create([
            'text' => 'test',
            'translation' => 'translation'
        ]);
        $from = route('glossaries.entries.edit', [$language->id, $entry->id]);
        $response = $this->from($from)->actingAs($user)->put(
            route('glossaries.entries.update', [$language->id, $entry->id]),
            [
                'text' => 'test',
                'translation' => 'test-translation'
            ]
        );

        $this->assertDatabaseCount('glossary', 2);
        // shouldn't be modified
        $this->assertDatabaseHas('glossary', [
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $response->assertSessionHasErrors(['text']);
        $response->assertRedirect($from);
    }

    public function testUpdateTooLong()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);
        $from = route('glossaries.entries.edit', [$language->id, $entry->id]);
        $response = $this->from($from)->actingAs($user)->put(
            route('glossaries.entries.update', [$language->id, $entry->id]),
            [
                'text' => str_repeat('a', 1024),
                'translation' => str_repeat('b', 1024)
            ]
        );

        $this->assertDatabaseCount('glossary', 1);
        // shouldn't be modified
        $this->assertDatabaseHas('glossary', [
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $response->assertSessionHasErrors(['text', 'translation']);
        $response->assertRedirect($from);
    }


    public function testDelete()
    {
        $language = $this->languages[0];
        $user = User::factory()->admin()->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $this->assertDatabaseCount('glossary', 1);

        $response = $this->actingAs($user)->delete(
            route('glossaries.entries.destroy', [$language->id, $entry->id])
        );

        $this->assertDatabaseCount('glossary', 0);

        $response->assertSessionHas('success');
        $response->assertRedirect(
            route('glossaries.entries.index', [$language->id]));
    }

    public function testDeleteRegularUser()
    {
        $language = $this->languages[0];
        $user = User::factory()->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $this->assertDatabaseCount('glossary', 1);

        $response = $this->actingAs($user)->delete(
            route('glossaries.entries.destroy', [$language->id, $entry->id])
        );

        $this->assertDatabaseCount('glossary', 1);

        $response->assertForbidden();
    }

    public function testDeleteLanguageManager()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[0]]
        )->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $this->assertDatabaseCount('glossary', 1);

        $response = $this->actingAs($user)->delete(
            route('glossaries.entries.destroy', [$language->id, $entry->id])
        );

        $this->assertDatabaseCount('glossary', 0);

        $response->assertSessionHas('success');
        $response->assertRedirect(
            route('glossaries.entries.index', [$language->id]));
    }

    public function testDeleteLanguageManagerForADifferentLanguage()
    {
        $language = $this->languages[0];
        $user = User::factory()->hasAttached(
            [$this->languages[1]]
        )->create();
        $entry = GlossaryEntry::factory()->for($language)->create([
            'text' => 'abcdefg',
            'translation' => 'tuvwxyz'
        ]);

        $this->assertDatabaseCount('glossary', 1);

        $response = $this->actingAs($user)->delete(
            route('glossaries.entries.destroy', [$language->id, $entry->id])
        );

        $this->assertDatabaseCount('glossary', 1);

        $response->assertForbidden();
    }
}
