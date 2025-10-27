<?php

use function Livewire\Volt\{state, mount};

// 結果画面の状態を定義
state([
    'answers' => [],
    'careLevel' => '',
    'careTime' => 0,
    'questions' => [],
    'groups' => [
        1 => '第1群　基本動作・起居動作機能の変化',
        2 => '第2群　生活機能（ADL・IADL）',
        3 => '第3群　認知機能（記憶・意思疎通）',
        4 => '第4群　社会的行動の評価',
        5 => '第5群　社会生活適応に関する評価',
        6 => '第6群　特別な医療行為',
        7 => '日常生活自立度（調査員）',
        8 => '主治医意見書（調査員）',
    ],
]);

// マウント時に入力データを受け取る
mount(function ($input = null) {
    if ($input) {
        try {
            $this->answers = json_decode(urldecode($input), true) ?? [];

            // 要介護認定基準時間の算出（仮の実装）
            $this->calculateCareTime();

            // 要介護度の判定
            $this->determineCareLevel();

            // 質問データの読み込み（本来はデータベースから取得するべき）
            $this->loadQuestions();
        } catch (\Exception $e) {
            // エラー処理
            $this->answers = [];
            $this->careLevel = 'エラー';
            $this->careTime = 0;
        }
    }
});

// 要介護認定基準時間を算出する（仮の実装）
$calculateCareTime = function () {
    // 実際には複雑なアルゴリズムで計算する
    // ここでは仮の実装として回答数に応じて時間を設定
    $baseTime = 30; // 基本時間（分）
    $additionalTimePerAnswer = 2; // 回答1つあたりの追加時間（分）

    $this->careTime = $baseTime + count($this->answers) * $additionalTimePerAnswer;
};

// 要介護度を判定する
$determineCareLevel = function () {
    // 実際には認定基準時間に基づいて判定する
    // ここでは仮の実装
    if ($this->careTime < 32) {
        $this->careLevel = '自立';
    } elseif ($this->careTime < 50) {
        $this->careLevel = '要支援1';
    } elseif ($this->careTime < 70) {
        $this->careLevel = '要支援2';
    } elseif ($this->careTime < 90) {
        $this->careLevel = '要介護1';
    } elseif ($this->careTime < 110) {
        $this->careLevel = '要介護2';
    } elseif ($this->careTime < 130) {
        $this->careLevel = '要介護3';
    } elseif ($this->careTime < 150) {
        $this->careLevel = '要介護4';
    } else {
        $this->careLevel = '要介護5';
    }
};

// 質問データを読み込む（本来はデータベースから取得するべき）
$loadQuestions = function () {
    // 入力画面と同じ質問データを使用（本来は共通のサービスから取得するべき）
    $this->questions = [
        // 第1群
        '1-1' => [
            'title' => '麻痺',
            'options' => ['左上肢', '右上肢', '左下肢', '右下肢', 'その他（四肢欠損）'],
            'type' => 'checkbox',
        ],
        '1-2' => [
            'title' => '拘縮',
            'options' => ['肩関節', '股関節', '膝関節', 'その他（四肢欠損）'],
            'type' => 'checkbox',
        ],
        '1-3' => [
            'title' => '寝返り',
            'options' => ['つかまらないでできる', '何かにつかまればできる', 'できない'],
            'type' => 'radio',
        ],
        // 以下省略（実際には全ての質問を含める）
    ];
};

// 入力画面に戻る
$backToInput = function () {
    $this->redirect(route('kaigo.input'));
};

?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">
                    要介護度算出結果
                </h2>

                <!-- 要介護度の表示 -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="text-xl font-bold text-center text-blue-800">
                        判定結果: {{ $this->careLevel }}
                    </h3>
                    <p class="text-center text-gray-600 mt-2">
                        要介護認定基準時間: {{ $this->careTime }}分
                    </p>
                </div>

                <!-- 要介護度の帯グラフ -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">要介護認定基準時間</h4>
                    <div class="relative h-8 bg-gray-200 rounded-full">
                        <!-- 各要介護度の境界線 -->
                        <div class="absolute h-full w-px bg-gray-400" style="left: 21.3%"></div>
                        <div class="absolute h-full w-px bg-gray-400" style="left: 35.7%"></div>
                        <div class="absolute h-full w-px bg-gray-400" style="left: 50%"></div>
                        <div class="absolute h-full w-px bg-gray-400" style="left: 64.3%"></div>
                        <div class="absolute h-full w-px bg-gray-400" style="left: 78.6%"></div>
                        <div class="absolute h-full w-px bg-gray-400" style="left: 92.9%"></div>

                        <!-- 現在の時間を示すマーカー -->
                        @php
                            $position = min(($this->careTime / 170) * 100, 100);
                        @endphp
                        <div class="absolute h-8 w-2 bg-red-500"
                            style="left: {{ $position }}%; transform: translateX(-50%)"></div>

                        <!-- 要介護度のラベル -->
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 10.7%">要支援1</div>
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 28.5%">要支援2</div>
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 42.9%">要介護1</div>
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 57.2%">要介護2</div>
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 71.5%">要介護3</div>
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 85.8%">要介護4</div>
                        <div class="absolute -bottom-6 text-xs text-black" style="left: 96.5%">要介護5</div>
                    </div>
                </div>

                <!-- 入力した調査項目と回答の表示 -->
                <div class="mt-10">
                    <h4 class="text-md font-medium text-gray-900 mb-4 p-2 bg-blue-100 rounded">入力内容の確認</h4>

                    @foreach ($this->groups as $groupId => $groupName)
                        <div class="mb-6">
                            <h5 class="font-medium text-gray-900 mb-2 p-1 bg-gray-100 rounded">{{ $groupName }}</h5>

                            <div class="space-y-2 pl-4">
                                @foreach ($this->questions as $questionId => $question)
                                    @if (str_starts_with($questionId, $groupId . '-'))
                                        <div class="border-b pb-2">
                                            <p class="text-sm font-medium text-black p-2 bg-gray-200 rounded">
                                                {{ $questionId }} {{ $question['title'] }}</p>

                                            @if (isset($this->answers[$questionId]))
                                                @if ($question['type'] === 'checkbox' && is_array($this->answers[$questionId]))
                                                    <p class="text-sm text-gray-800 pl-2 bg-gray-50 p-1 rounded mt-1">
                                                        {{ implode('、', $this->answers[$questionId]) }}
                                                    </p>
                                                @else
                                                    <p class="text-sm text-gray-800 pl-2 bg-gray-50 p-1 rounded mt-1">
                                                        {{ $this->answers[$questionId] }}
                                                    </p>
                                                @endif
                                            @else
                                                <p class="text-sm text-gray-500 pl-2 italic mt-1">未回答</p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- 戻るボタン -->
                <div class="mt-6">
                    <button wire:click="backToInput()"
                        class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        入力画面に戻る
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
