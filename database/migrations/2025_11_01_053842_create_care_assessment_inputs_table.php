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
        // 既にテーブルが存在する場合はスキップ
        if (Schema::hasTable('care_assessment_inputs')) {
            return;
        }

        Schema::create('care_assessment_inputs', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->comment('タイトル/名称');
            $table->json('answers')->comment('回答データ（JSON形式）');
            $table->integer('care_time')->nullable()->comment('要介護認定基準時間（分）');
            $table->string('care_level')->nullable()->comment('要介護度の区分');
            $table->string('status')->default('draft')->comment('進捗状況（draft: 一時保存、completed: 完了）');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('ユーザーID');
            $table->timestamps();

            // インデックスを追加
            // user_idはforeignId()で自動的にインデックスが作成されるため、追加不要
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_assessment_inputs');
    }
};
