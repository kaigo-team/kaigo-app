<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\CareAssessmentInput;
use App\Services\SurveyQuestions;
use Illuminate\Support\Facades\Auth;

// 状態定義
state([
    'inputs' => [],
    'showFilters' => false,
    'filters' => [
        'status' => null,
        'care_level' => null,
        'created_from' => null,
        'created_to' => null,
    ],
]);

// データ取得
$loadInputs = function () {
    $query = CareAssessmentInput::query();

    // ユーザーIDでフィルター（認証使用時）
    if (Auth::check()) {
        $query->where('user_id', Auth::id());
    } else {
        // 認証していない場合はuser_idがnullのデータのみ
        $query->whereNull('user_id');
    }

    // フィルター適用
    if ($this->filters['status']) {
        $query->where('status', $this->filters['status']);
    }

    if ($this->filters['care_level']) {
        $query->where('care_level', $this->filters['care_level']);
    }

    if ($this->filters['created_from']) {
        $query->whereDate('created_at', '>=', $this->filters['created_from']);
    }

    if ($this->filters['created_to']) {
        $query->whereDate('created_at', '<=', $this->filters['created_to']);
    }

    // 更新日時の新しい順でソート
    $this->inputs = $query->orderBy('updated_at', 'desc')->get();
};

// 初期化
mount(function () {
    $this->loadInputs();
});

// フィルターの表示/非表示を切り替える
$toggleFilters = function () {
    $this->showFilters = !$this->showFilters;
};

// フィルターを適用する
$applyFilters = function () {
    $this->loadInputs();
    $this->showFilters = false;
};

// フィルターをリセットする
$resetFilters = function () {
    $this->filters = [
        'status' => null,
        'care_level' => null,
        'created_from' => null,
        'created_to' => null,
    ];
    $this->loadInputs();
    $this->showFilters = false;
};

// 進捗率を計算するcomputed関数
$getProgressPercentage = function ($input) {
    $answers = $input->answers ?? [];
    $totalQuestions = count(SurveyQuestions::getAllQuestions());
    $answeredQuestions = count($answers);

    if ($totalQuestions === 0) {
        return 0;
    }

    return ($answeredQuestions / $totalQuestions) * 100;
};

// 進捗バーの色を取得する関数
$getProgressColor = function ($percentage) {
    if ($percentage <= 25) {
        return 'bg-red-500';
    } elseif ($percentage <= 50) {
        return 'bg-orange-500';
    } elseif ($percentage <= 75) {
        return 'bg-yellow-500';
    } elseif ($percentage < 100) {
        return 'bg-blue-500';
    } else {
        return 'bg-green-500';
    }
};

// 編集画面に遷移
$editInput = function ($id) {
    $this->redirect(route('kaigo.input.edit', ['id' => $id]));
};

// 結果画面に遷移
$viewResult = function ($id) {
    $input = CareAssessmentInput::find($id);
    if ($input && $input->answers) {
        $answersJson = urlencode(json_encode($input->answers));
        $this->redirect(route('kaigo.result', ['input' => $answersJson]));
    }
};

// 削除する
$deleteInput = function ($id) {
    $input = CareAssessmentInput::find($id);
    if ($input && (Auth::guest() || $input->user_id === Auth::id())) {
        $input->delete();
        $this->loadInputs();
        session()->flash('message', '削除しました');
    }
};

?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- ヘッダー -->
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900">
                    要介護度算出アプリ
                </h2>
                <div class="flex gap-2">
                    <button wire:click="toggleFilters"
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        フィルター
                    </button>
                    <a href="{{ route('kaigo.input') }}"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        新規作成
                    </a>
                </div>
            </div>

            <!-- メッセージ表示 -->
            @if (session()->has('message'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('message') }}
                </div>
            @endif

            <!-- フィルターエリア -->
            @if ($showFilters)
                <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">フィルター</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 進捗状況 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">進捗状況</label>
                            <select wire:model="filters.status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">すべて</option>
                                <option value="draft">一時保存</option>
                                <option value="completed">完了</option>
                            </select>
                        </div>

                        <!-- 要介護度 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">要介護度</label>
                            <select wire:model="filters.care_level"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">すべて</option>
                                <option value="非該当">非該当</option>
                                <option value="要支援1">要支援1</option>
                                <option value="要支援2・要介護1">要支援2・要介護1</option>
                                <option value="要介護2">要介護2</option>
                                <option value="要介護3">要介護3</option>
                                <option value="要介護4">要介護4</option>
                                <option value="要介護5">要介護5</option>
                            </select>
                        </div>

                        <!-- 作成日時（開始） -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">作成日時（開始）</label>
                            <input type="date" wire:model="filters.created_from"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- 作成日時（終了） -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">作成日時（終了）</label>
                            <input type="date" wire:model="filters.created_to"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button wire:click="applyFilters"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            適用
                        </button>
                        <button wire:click="resetFilters"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            リセット
                        </button>
                    </div>
                </div>
            @endif

            <!-- 一覧表示 -->
            @if (count($inputs) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($inputs as $input)
                        <div class="border rounded-lg p-4 bg-white shadow-md hover:shadow-lg transition-shadow">
                            <!-- タイトル -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2">
                                {{ $input->title ?: '無題' }}
                            </h3>

                            <!-- 進捗バー -->
                            @php
                                $progressPercentage = $this->getProgressPercentage($input);
                                $progressColor = $this->getProgressColor($progressPercentage);
                                $answeredQuestions = count($input->answers ?? []);
                                $totalQuestions = count(SurveyQuestions::getAllQuestions());
                            @endphp
                            <div class="mb-2">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm text-gray-600">進捗</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ number_format($progressPercentage, 1) }}%
                                        ({{ $answeredQuestions }}/{{ $totalQuestions }})
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="{{ $progressColor }} h-2 rounded-full transition-all"
                                        style="width: {{ $progressPercentage }}%"></div>
                                </div>
                            </div>

                            <!-- 情報 -->
                            <div class="space-y-1 mb-4">
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">作成日時:</span>
                                    {{ $input->created_at ? $input->created_at->format('Y/m/d H:i') : '-' }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">更新日時:</span>
                                    {{ $input->updated_at ? $input->updated_at->format('Y/m/d H:i') : '-' }}
                                </div>
                                @if ($input->care_level)
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-600">要介護度:</span>
                                        <span
                                            class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">{{ $input->care_level }}</span>
                                    </div>
                                @endif
                                @if ($input->care_time)
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">要介護認定基準時間:</span> {{ $input->care_time }}分
                                    </div>
                                @endif
                                <div class="text-sm">
                                    <span class="font-medium text-gray-600">進捗状況:</span>
                                    @if ($input->status === 'completed')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">完了</span>
                                    @else
                                        <span
                                            class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">一時保存</span>
                                    @endif
                                </div>
                            </div>

                            <!-- ボタン -->
                            <div class="flex gap-2">
                                <button wire:click="editInput({{ $input->id }})"
                                    class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                    編集
                                </button>
                                @if ($input->answers && count($input->answers) > 0)
                                    <button wire:click="viewResult({{ $input->id }})"
                                        class="flex-1 px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                        結果を見る
                                    </button>
                                @endif
                                <button wire:click="deleteInput({{ $input->id }})" wire:confirm="削除してもよろしいですか？"
                                    class="px-3 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">
                                    削除
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">データがありません</p>
                    <a href="{{ route('kaigo.input') }}"
                        class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        新規作成
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
