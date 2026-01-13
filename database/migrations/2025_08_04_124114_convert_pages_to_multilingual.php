<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup existing data and convert to multilingual format
        $pages = DB::table('pages')->get();
        
        // Store converted data temporarily
        $convertedPages = [];
        foreach ($pages as $page) {
            $convertedPages[] = [
                'id' => $page->id,
                'name' => json_encode(['cs' => $page->name]),
                'slug' => json_encode(['cs' => $page->slug]),
                'description' => json_encode(['cs' => $page->description ?? '']),
                'content' => json_encode(['cs' => $page->content ?? '']),
            ];
        }

        Schema::table('pages', function (Blueprint $table) {
            // Add new multilingual JSON columns
            $table->json('name_new')->nullable()->after('category_id');
            $table->json('slug_new')->nullable()->after('name_new');
            $table->json('description_new')->nullable()->after('slug_new');
            $table->json('content_new')->nullable()->after('description_new');
            
            // Add missing meta columns as JSON
            $table->json('meta_title')->nullable()->after('content_new');
            $table->json('meta_description')->nullable()->after('meta_title');
            $table->json('meta_keywords')->nullable()->after('meta_description');
        });

        // Update pages with converted data
        foreach ($convertedPages as $convertedPage) {
            DB::table('pages')
                ->where('id', $convertedPage['id'])
                ->update([
                    'name_new' => $convertedPage['name'],
                    'slug_new' => $convertedPage['slug'],
                    'description_new' => $convertedPage['description'],
                    'content_new' => $convertedPage['content'],
                    'meta_title' => json_encode(['cs' => '']),
                    'meta_description' => json_encode(['cs' => '']),
                    'meta_keywords' => json_encode(['cs' => '']),
                ]);
        }

        // Drop old columns and rename new ones
        Schema::table('pages', function (Blueprint $table) {
            // Drop indexes first if they exist
            $schema = DB::getSchemaBuilder();
            $indexNames = $schema->getIndexes('pages');
            
            foreach ($indexNames as $index) {
                if (in_array($index['name'], ['pages_slug_index', 'pages_slug_unique'])) {
                    $table->dropIndex($index['name']);
                }
            }
            
            $table->dropColumn(['name', 'slug', 'description', 'content']);
        });
        
        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('name_new', 'name');
            $table->renameColumn('slug_new', 'slug');
            $table->renameColumn('description_new', 'description');
            $table->renameColumn('content_new', 'content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get current multilingual data
        $pages = DB::table('pages')->get();
        
        // Convert back to single language (CS)
        $convertedPages = [];
        foreach ($pages as $page) {
            $name = json_decode($page->name, true);
            $slug = json_decode($page->slug, true);
            $description = json_decode($page->description, true);
            $content = json_decode($page->content, true);
            
            $convertedPages[] = [
                'id' => $page->id,
                'name' => $name['cs'] ?? '',
                'slug' => $slug['cs'] ?? '',
                'description' => $description['cs'] ?? '',
                'content' => $content['cs'] ?? '',
            ];
        }

        Schema::table('pages', function (Blueprint $table) {
            // Add old columns back
            $table->string('name_old')->after('category_id');
            $table->string('slug_old')->unique()->after('name_old');
            $table->text('description_old')->nullable()->after('slug_old');
            $table->longText('content_old')->nullable()->after('description_old');
        });

        // Restore old data
        foreach ($convertedPages as $convertedPage) {
            DB::table('pages')
                ->where('id', $convertedPage['id'])
                ->update([
                    'name_old' => $convertedPage['name'],
                    'slug_old' => $convertedPage['slug'],
                    'description_old' => $convertedPage['description'],
                    'content_old' => $convertedPage['content'],
                ]);
        }

        Schema::table('pages', function (Blueprint $table) {
            // Drop multilingual columns
            $table->dropColumn([
                'name', 'slug', 'description', 'content',
                'meta_title', 'meta_description', 'meta_keywords'
            ]);
        });
        
        Schema::table('pages', function (Blueprint $table) {
            // Rename old columns back
            $table->renameColumn('name_old', 'name');
            $table->renameColumn('slug_old', 'slug');
            $table->renameColumn('description_old', 'description');
            $table->renameColumn('content_old', 'content');
        });
    }
};
