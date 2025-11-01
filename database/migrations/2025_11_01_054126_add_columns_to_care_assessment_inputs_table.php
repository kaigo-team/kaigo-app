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
        Schema::table('care_assessment_inputs', function (Blueprint $table) {
            // 既存のカラムが存在しない場合のみ追加
            if (!Schema::hasColumn('care_assessment_inputs', 'title')) {
                $table->string('title')->nullable()->comment('タイトル/名称')->after('id');
            }
            if (!Schema::hasColumn('care_assessment_inputs', 'answers')) {
                $table->json('answers')->comment('回答データ（JSON形式）')->after('title');
            }
            if (!Schema::hasColumn('care_assessment_inputs', 'care_time')) {
                $table->integer('care_time')->nullable()->comment('要介護認定基準時間（分）')->after('answers');
            }
            if (!Schema::hasColumn('care_assessment_inputs', 'care_level')) {
                $table->string('care_level')->nullable()->comment('要介護度の区分')->after('care_time');
            }
            if (!Schema::hasColumn('care_assessment_inputs', 'status')) {
                $table->string('status')->default('draft')->comment('進捗状況（draft: 一時保存、completed: 完了）')->after('care_level');
            }
            if (!Schema::hasColumn('care_assessment_inputs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('ユーザーID')->after('status');
                // foreignId()は自動的にインデックスを作成するため、ここではインデックス追加不要
            }

            // インデックスの追加（存在しない場合のみ）
            // user_idはforeignId()で自動的にインデックスが作成されるため、インデックス追加は不要
            if (!$this->hasIndex('care_assessment_inputs', 'status')) {
                $table->index('status');
            }
            if (!$this->hasIndex('care_assessment_inputs', 'created_at')) {
                $table->index('created_at');
            }
            if (!$this->hasIndex('care_assessment_inputs', 'updated_at')) {
                $table->index('updated_at');
            }
        });
    }

    /**
     * インデックスが存在するかチェック（SQLite対応）
     */
    private function hasIndex(string $table, string $column): bool
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $indexName = "{$table}_{$column}_index";

            if ($driver === 'sqlite') {
                // SQLiteの場合
                $result = DB::select(
                    "SELECT name FROM sqlite_master 
                    WHERE type = 'index' AND name = ?",
                    [$indexName]
                );
                return !empty($result);
            } else {
                // MySQL/PostgreSQLの場合
                $databaseName = $connection->getDatabaseName();
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                    WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return isset($result[0]) && $result[0]->count > 0;
            }
        } catch (\Exception $e) {
            // エラーが発生した場合は安全のためtrueを返す（インデックス追加をスキップ）
            return true;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_assessment_inputs', function (Blueprint $table) {
            // インデックスを削除
            // user_idのインデックスはforeignId()で自動的に作成されるため、外部キー削除時に自動削除される
            if ($this->hasIndex('care_assessment_inputs', 'updated_at')) {
                $table->dropIndex(['updated_at']);
            }
            if ($this->hasIndex('care_assessment_inputs', 'created_at')) {
                $table->dropIndex(['created_at']);
            }
            if ($this->hasIndex('care_assessment_inputs', 'status')) {
                $table->dropIndex(['status']);
            }

            // 外部キーを削除
            if (Schema::hasColumn('care_assessment_inputs', 'user_id')) {
                $table->dropForeign(['user_id']);
            }

            // カラムを削除
            if (Schema::hasColumn('care_assessment_inputs', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('care_assessment_inputs', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('care_assessment_inputs', 'care_level')) {
                $table->dropColumn('care_level');
            }
            if (Schema::hasColumn('care_assessment_inputs', 'care_time')) {
                $table->dropColumn('care_time');
            }
            if (Schema::hasColumn('care_assessment_inputs', 'answers')) {
                $table->dropColumn('answers');
            }
            if (Schema::hasColumn('care_assessment_inputs', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
