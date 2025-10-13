<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // INDEX COMPOSITE le plus important (filtrage principal)
            $table->index(['status', 'is_draft'], 'products_status_draft_idx');
            
            // INDEX sur productable_type (polymorphisme)
            $table->index('productable_type', 'products_productable_type_idx');
            
            // INDEX sur category_id  
            $table->index('category_id', 'products_category_id_idx');
            
            // INDEX sur created_at (tri par défaut)
            $table->index('created_at', 'products_created_at_idx');
            
            // INDEX sur price (tri par prix)
            $table->index('price', 'products_price_idx');
        });

        // INDEX FULLTEXT pour recherche (PostgreSQL)
        DB::statement('CREATE INDEX products_fulltext_idx ON products USING gin(to_tsvector(\'french\', name || \' \' || coalesce(description, \'\')))');
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_draft_idx');
            $table->dropIndex('products_productable_type_idx');
            $table->dropIndex('products_category_id_idx');
            $table->dropIndex('products_created_at_idx');
            $table->dropIndex('products_price_idx');
        });
        
        DB::statement('DROP INDEX IF EXISTS products_fulltext_idx');
    }
};