<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('source_id');
            $table->string('title');
            $table->text('slug');
            $table->longText('description');
            $table->text('source_url')->nullable();;
            $table->text('thumbnail_url')->nullable();;
            $table->timestamp('published_at')->nullable();;
            $table->string('author')->nullable();;

            $table->index('category_id');
            $table->index('source_id');
            $table->index('published_at');

            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('source_id')->references('id')->on('sources');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
