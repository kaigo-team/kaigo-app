<?php

use function Livewire\Volt\{state, computed, mount};
use App\Services\SurveyQuestions;
use App\Models\CareAssessmentInput;
use Illuminate\Support\Facades\Auth;

// 調査項目のグループとその内容を定義
state([
    'currentGroup' => 1,
    'answers' => [],
    'groups' => SurveyQuestions::getGroups(),
    'questions' => SurveyQuestions::getAllQuestions(),
    'inputId' => null,
    'saving' => false,
    'saved' => false,
    'showModal' => false,
    'modalMessage' => '',
    'modalType' => 'success', // 'success' or 'error'
]);

// 編集時は既存データを読み込む
mount(function ($id = null) {
    if ($id) {
        $this->inputId = $id;
        $input = CareAssessmentInput::find($id);
        if ($input) {
            $this->answers = $input->answers ?? [];
        }
    }
});

/**
 * 現在表示中のグループに属する質問のみを取得するcomputed関数
 *
 * 現在選択されているグループのプレフィックス（例: '1-'）で始まる質問のみをフィルタリングします。
 * これにより、画面に表示する質問を現在のグループに限定できます。
 *
 * @return array 現在のグループに属する質問の配列
 */
$currentGroupQuestions = computed(function () {
    // 現在のグループのプレフィックスを作成（例: '1-'）
    $groupPrefix = $this->currentGroup . '-';
    $questions = [];

    // 全質問から現在のグループに属する質問のみをフィルタリング
    foreach ($this->questions as $key => $question) {
        if (str_starts_with($key, $groupPrefix)) {
            $questions[$key] = $question;
        }
    }

    return $questions;
});

/**
 * 回答の進捗率を計算するcomputed関数
 *
 * 全質問数に対する回答済み質問数の割合をパーセンテージで計算します。
 * この値は進捗バーの表示に使用されます。
 *
 * @return float 進捗率（0〜100のパーセンテージ値）
 */
$progressPercentage = computed(function () {
    // 全質問数を取得
    $totalQuestions = count($this->questions);
    // 回答済みの質問数を取得（answersの要素数）
    $answeredQuestions = count($this->answers);

    // 進捗率をパーセンテージで計算して返す
    return ($answeredQuestions / $totalQuestions) * 100;
});

/**
 * 質問に対する回答を処理する関数
 *
 * @param string $questionId   質問ID（例: '1-1', '2-3'など）
 * @param string $answer       選択された回答の値
 * @param bool $isCheckbox     チェックボックス形式の質問かどうか（複数選択可能な場合はtrue）
 *
 * この関数は質問への回答を処理し、$answers配列に保存します。
 * チェックボックスの場合は配列として保存し、ラジオボタンの場合は単一の値として保存します。
 */
$answerQuestion = function ($questionId, $answer, $isCheckbox = false) {
    // チェックボックス（複数選択可能）の場合の処理
    if ($isCheckbox) {
        // まだ回答がない場合は空の配列を初期化
        if (!isset($this->answers[$questionId])) {
            $this->answers[$questionId] = [];
        }

        // 既に選択されている場合は選択を解除（トグル動作）
        $index = array_search($answer, $this->answers[$questionId]);
        if ($index !== false) {
            // 選択を解除して配列を再インデックス
            unset($this->answers[$questionId][$index]);
            $this->answers[$questionId] = array_values($this->answers[$questionId]);
        } else {
            // 新しい選択を追加
            $this->answers[$questionId][] = $answer;
        }
    } else {
        // ラジオボタン（単一選択）の場合は値を直接設定
        $this->answers[$questionId] = $answer;
    }
};

/*
 *回答から介護時間を計算する関数
 */
$getCareTime = function ($answers) {
    //食事
    if ($answers['2-4'] == question('2-4')['options'][0] || $answers['2-4'] == question('2-4')['options'][1]) {
        //自立（介助なし）or見守り等
    }
    return 0;
};

/**
 * 前のグループに移動する関数
 *
 * 現在のグループが1より大きい場合に、前のグループに移動します。
 * 最初のグループ（1）の場合は何も実行されません。
 */
$previousGroup = function () {
    if ($this->currentGroup > 1) {
        $this->currentGroup--;
    }
};

/**
 * 次のグループに移動する関数
 *
 * 現在のグループが最後のグループより小さい場合に、次のグループに移動します。
 * 最後のグループの場合は何も実行されません。
 */
$nextGroup = function () {
    if ($this->currentGroup < count($this->groups)) {
        $this->currentGroup++;
    }
};

/**
 * 一時保存する関数
 *
 * 現在の回答データをデータベースに保存します。
 * 保存成功時はモーダルでメッセージを表示し、入力画面に留まります。
 */
$saveDraft = function () {
    $this->saving = true;
    $this->saved = false;
    $this->showModal = false;

    try {
        $data = [
            'answers' => $this->answers,
            'status' => 'draft',
            'user_id' => Auth::id(),
        ];

        if ($this->inputId) {
            // 編集時は更新
            $input = CareAssessmentInput::find($this->inputId);
            if ($input) {
                $input->update($data);
            }
        } else {
            // 新規作成時
            $input = CareAssessmentInput::create($data);
            $this->inputId = $input->id;
        }

        $this->saved = true;
        $this->modalMessage = '一時保存しました';
        $this->modalType = 'success';
        $this->showModal = true;
    } catch (\Exception $e) {
        $this->modalMessage = '保存に失敗しました: ' . $e->getMessage();
        $this->modalType = 'error';
        $this->showModal = true;
    } finally {
        $this->saving = false;
    }
};

/**
 * モーダルを閉じる関数
 */
$closeModal = function () {
    $this->showModal = false;
    $this->modalMessage = '';
};

/**
 * 結果画面に移動する関数
 *
 * 入力された回答データをJSON形式に変換し、URLエンコードして結果画面に渡します。
 * 結果画面では、このデータを使用して要介護度を算出します。
 */
$showResult = function () {
    // 回答をJSONに変換してURLエンコードし、結果画面に渡す
    $answersJson = urlencode(json_encode($this->answers));
    $this->redirect(route('kaigo.result', ['input' => $answersJson]));
};

/**
 * 一覧画面に戻る関数
 */
$backToIndex = function () {
    $this->redirect(route('kaigo.index'));
};

?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">
                    要介護度算出アプリ
                </h2>

                <!-- 固定された進捗バー -->
                <div class="sticky top-0 z-10 pt-2 pb-2 bg-white px-4 py-2">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $this->progressPercentage }}%"></div>
                    </div>
                </div>

                <!-- グループタイトル -->
                <h3 class="text-md font-medium text-gray-900 mb-4 p-2 bg-blue-100 rounded">
                    {{ $this->groups[$this->currentGroup] }}
                </h3>

                <!-- 質問リスト -->
                <div class="space-y-6">
                    @foreach ($this->currentGroupQuestions as $questionId => $question)
                        <div class="border p-4 rounded-lg bg-gray-50">
                            <h4 class="font-medium text-gray-900 mb-2 p-2 bg-gray-200 rounded">{{ $questionId }}
                                {{ $question['title'] }}</h4>

                            <div class="space-y-2">
                                @if ($question['type'] === 'radio')
                                    @foreach ($question['options'] as $option)
                                        <div class="flex items-center p-2 hover:bg-gray-100 rounded">
                                            <input type="radio" id="{{ $questionId }}_{{ $loop->index }}"
                                                name="{{ $questionId }}" value="{{ $option }}"
                                                @if (isset($this->answers[$questionId]) && $this->answers[$questionId] === $option) checked @endif
                                                wire:click="answerQuestion('{{ $questionId }}', '{{ addslashes($option) }}')"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                                            <label for="{{ $questionId }}_{{ $loop->index }}"
                                                class="ml-2 text-sm font-medium text-black">
                                                {{ $option }}
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    @foreach ($question['options'] as $option)
                                        <div class="flex items-center p-2 hover:bg-gray-100 rounded">
                                            <input type="checkbox" id="{{ $questionId }}_{{ $loop->index }}"
                                                name="{{ $questionId }}[]" value="{{ $option }}"
                                                @if (isset($this->answers[$questionId]) && in_array($option, $this->answers[$questionId])) checked @endif
                                                wire:click="answerQuestion('{{ $questionId }}', '{{ addslashes($option) }}', true)"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <label for="{{ $questionId }}_{{ $loop->index }}"
                                                class="ml-2 text-sm font-medium text-black">
                                                {{ $option }}
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>


                <!-- ナビゲーションボタン -->
                <div class="flex justify-between mt-6">
                    <button wire:click="backToIndex"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        一覧に戻る
                    </button>

                    <div class="flex gap-2">
                        <button wire:click="saveDraft" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove>一時保存</span>
                            <span wire:loading>保存中...</span>
                        </button>

                        <button wire:click="previousGroup" @if ($this->currentGroup === 1) disabled @endif
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md @if ($this->currentGroup === 1) opacity-50 cursor-not-allowed @endif">
                            前へ
                        </button>

                        @if ($this->currentGroup < count($this->groups))
                            <button wire:click="nextGroup"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                次へ
                            </button>
                        @else
                            <button wire:click="showResult"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                結果を見る
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- モーダル（画面中央表示） -->
    <div class="fixed inset-0 z-10 flex items-center justify-center transition-opacity" x-data="{ show: @entangle('showModal') }"
        x-show="show" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" wire:click="closeModal"
        style="background-color: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.stop>
            <div class="p-6">
                @if ($modalType === 'success')
                    <!-- 成功メッセージ -->
                    <div class="flex items-start">
                        <div class="flex-1">
                            <!-- アイコンと保存完了を1行で横並び -->
                            <div class="flex items-center mb-2">
                                <svg class="h-10 w-10 text-green-500 mr-3 flex-shrink-0" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-2xl font-bold text-gray-900">
                                    保存完了
                                </h3>
                            </div>
                            <!-- メッセージを2行目に表示 -->
                            <p class="text-sm text-gray-600">
                                {{ $modalMessage }}
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <button wire:click="closeModal" type="button"
                                class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- エラーメッセージ -->
                    <div class="flex items-start">
                        <div class="flex-1">
                            <!-- アイコンとエラーを1行で横並び -->
                            <div class="flex items-center mb-2">
                                <svg class="h-10 w-10 text-red-500 mr-3 flex-shrink-0" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-2xl font-bold text-gray-900">
                                    エラー
                                </h3>
                            </div>
                            <!-- メッセージを2行目に表示 -->
                            <p class="text-sm text-gray-600">
                                {{ $modalMessage }}
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <button wire:click="closeModal" type="button"
                                class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            <div class="bg-gray-50 px-6 py-3 flex justify-end">
                <button wire:click="closeModal" type="button"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>
