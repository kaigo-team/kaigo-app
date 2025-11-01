<?php

use function Livewire\Volt\{state, computed, mount};
use App\Models\CareAssessmentInput;
use App\Services\SurveyQuestions;
use Illuminate\Support\Facades\Auth;

// 状態定義
state([
    'inputs' => [],
    'showFilterModal' => false,
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

// フィルターを適用する
$applyFilters = function () {
    $this->loadInputs();
    $this->showFilterModal = false;
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
    $this->showFilterModal = false;
};

// フィルターモーダルを開く
$openFilterModal = function () {
    $this->showFilterModal = true;
};

// フィルターモーダルを閉じる
$closeFilterModal = function () {
    $this->showFilterModal = false;
};

// 進捗率を計算するcomputed関数
$getProgressPercentage = function ($input) {
    $answers = $input->answers ?? [];
    $allQuestions = SurveyQuestions::getAllQuestions();

    // 必須質問（ラジオボタンのみ）の総数を計算
    $requiredQuestions = 0;
    $answeredRequiredQuestions = 0;

    foreach ($allQuestions as $questionId => $question) {
        // ラジオボタンの質問のみをカウント（必須質問）
        if ($question['type'] === 'radio') {
            $requiredQuestions++;
            // 回答済みかどうかをチェック
            if (isset($answers[$questionId]) && !empty($answers[$questionId])) {
                $answeredRequiredQuestions++;
            }
        }
    }

    // 必須質問が0の場合は0%を返す
    if ($requiredQuestions === 0) {
        return 0;
    }

    return ($answeredRequiredQuestions / $requiredQuestions) * 100;
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
        // inputIdも一緒に渡して、結果画面から戻る際に使用できるようにする
        $this->redirect(route('kaigo.result', ['input' => $answersJson, 'id' => $id]));
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
        <div class="bg-white shadow sm:rounded-lg">
            <div class="p-4 sm:p-8 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">
                    要介護度算出アプリ
                </h2>
            </div>

            <!-- ヒーローセクション -->
            <div class="relative p-6 sm:p-8 mb-6 overflow-hidden"
                style="background-image: url('{{ asset('hero_section_bg.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat; height: 500px;">
                <!-- オーバーレイ（背景画像を少し暗くしてテキストを見やすくする） -->
                <div class="absolute inset-0 " style="background-color: rgba(0, 0, 0, 0.4);"></div>
                <!-- コンテンツ -->
                <div class="relative text-center z-10 h-full flex justify-center items-center flex-col">
                    <h3 class="text-2xl sm:text-3xl font-bold text-white mb-3">
                        新しい要介護度算出を開始
                    </h3>
                    <p class="text-white mb-6 text-sm sm:text-base">
                        調査項目に回答することで、要介護度を自動で算出できます。<br class="hidden sm:block">
                        入力内容は一時保存できるので、途中で中断しても安心です。
                    </p>
                    <a href="{{ route('kaigo.input') }}"
                        class="inline-block px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg">
                        新規作成
                    </a>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">

                <!-- メッセージ表示 -->
                @if (session()->has('message'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('message') }}
                    </div>
                @endif

                <div class="flex gap-2 mb-5">
                    <button wire:click="openFilterModal"
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        フィルター
                    </button>
                </div>

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
                                            <span
                                                class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">完了</span>
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
                                    @php
                                        $progressPercentage = $this->getProgressPercentage($input);
                                    @endphp
                                    @if ($progressPercentage >= 100)
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
    <!-- フィルターモーダル -->
    <div class="fixed inset-0 z-10 flex items-center justify-center transition-opacity" x-data="{ show: @entangle('showFilterModal') }"
        x-show="show" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" wire:click="closeFilterModal"
        style="background-color: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 transform transition-all"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.stop>
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">フィルター</h3>
                    <button wire:click="closeFilterModal" type="button"
                        class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- 進捗状況 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">進捗状況</label>
                        <select wire:model="filters.status"
                            class="w-full rounded-md border-gray-300 shadow-sm text-gray-900 bg-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">すべて</option>
                            <option value="draft">一時保存</option>
                            <option value="completed">完了</option>
                        </select>
                    </div>

                    <!-- 要介護度 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">要介護度</label>
                        <select wire:model="filters.care_level"
                            class="w-full rounded-md border-gray-300 shadow-sm text-gray-900 bg-white focus:border-blue-500 focus:ring-blue-500">
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
                            class="w-full rounded-md border-gray-300 shadow-sm text-gray-900 bg-white focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- 作成日時（終了） -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">作成日時（終了）</label>
                        <input type="date" wire:model="filters.created_to"
                            class="w-full rounded-md border-gray-300 shadow-sm text-gray-900 bg-white focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                <button wire:click="resetFilters" type="button"
                    class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    リセット
                </button>
                <button wire:click="applyFilters" type="button"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    適用
                </button>
            </div>
        </div>
    </div>
</div>
