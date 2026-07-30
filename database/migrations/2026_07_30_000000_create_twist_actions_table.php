<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Obelaw\Twist\Base\BaseMigration;

return new class extends BaseMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix . 'twist_actions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('class_name')->unique();
            $table->timestamps();
        });

        Schema::create($this->prefix . 'twist_action_causers', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('causerable');
            $table->timestamps();

            $table->unique(['causerable_type', 'causerable_id']);
        });

        Schema::create($this->prefix . 'twist_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained($this->prefix . 'twist_actions')->cascadeOnDelete();
            $table->foreignId('causer_id')->nullable()->constrained($this->prefix . 'twist_action_causers')->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->json('payload')->nullable();
            $table->string('status')->default('success');
            $table->text('exception')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action_id', 'causer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix . 'twist_action_logs');
        Schema::dropIfExists($this->prefix . 'twist_action_causers');
        Schema::dropIfExists($this->prefix . 'twist_actions');
    }
};
