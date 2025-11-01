<?php

use function Livewire\Volt\{state, mount};
use App\Services\SurveyQuestions;

// 結果画面の状態を定義
state([
    'answers' => [],
    'careLevel' => '',
    'careTime' => 0,
    'questions' => [],
    'groups' => SurveyQuestions::getGroups(),
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

// 要介護認定基準時間を算出する
$calculateCareTime = function () {
    // CareTimeLogicサービスを利用して要介護認定基準時間を算出
    $careTimeLogic = new \App\Services\CareTimeLogic();
    $this->careTime = $careTimeLogic->calculateCareTime($this->answers);
};

// 要介護度を判定する
$determineCareLevel = function () {
    // CareTimeLogicサービスを利用して要介護度を判定
    $careTimeLogic = new \App\Services\CareTimeLogic();
    $this->careLevel = $careTimeLogic->determineCareLevel($this->careTime);
};

// 質問データを読み込む
$loadQuestions = function () {
    // 共通サービスから質問データを取得
    $this->questions = SurveyQuestions::getAllQuestions();
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
                <div class="mt-6 p-6 bg-blue-100 rounded-lg border-2 border-blue-300 shadow-md">
                    <h3 class="text-2xl font-bold text-center text-blue-800 mb-2">
                        判定結果: <span class="text-3xl">{{ $this->careLevel }}</span>
                    </h3>
                    <p class="text-center text-gray-700 mt-3 text-lg">
                        要介護認定基準時間: <span class="font-bold">{{ $this->careTime }}分</span>
                    </p>
                    @php
                        $careTimeLogic = new \App\Services\CareTimeLogic();
                        $intermediateScore = $careTimeLogic->calculateIntermediateScore($this->answers);
                        $physicalScore = $careTimeLogic->calculatePhysicalFunctionScore($this->answers);
                        $lifeScore = $careTimeLogic->calculateLifeFunctionScore($this->answers);
                        $cognitiveScore = $careTimeLogic->calculateCognitiveFunctionScore($this->answers);
                        $mentalScore = $careTimeLogic->calculateMentalBehaviorDisorderScore($this->answers);
                        $socialScore = $careTimeLogic->calculateSocialAdaptationScore($this->answers);
                    @endphp
                    <p class="text-center text-gray-700 mt-2">
                        中間得点: <span class="font-semibold">{{ number_format($intermediateScore, 1) }}点</span>
                    </p>
                    <div class="mt-4 p-3 bg-white rounded-lg border border-blue-200 shadow-sm">
                        <h4 class="text-base font-medium text-blue-700 mb-3 text-center">群別中間得点</h4>
                        <div class="grid grid-cols-1 gap-2 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">第1群（身体機能・起居動作）:</span>
                                <span class="font-medium text-gray-600">{{ number_format($physicalScore, 1) }}点</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">第2群（生活機能）:</span>
                                <span class="font-medium text-gray-600">{{ number_format($lifeScore, 1) }}点</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">第3群（認知機能）:</span>
                                <span class="font-medium text-gray-600">{{ number_format($cognitiveScore, 1) }}点</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">第4群（精神・行動障害）:</span>
                                <span class="font-medium text-gray-600">{{ number_format($mentalScore, 1) }}点</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">第5群（社会生活への適応）:</span>
                                <span class="font-medium text-gray-600">{{ number_format($socialScore, 1) }}点</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 要介護度の帯グラフ -->
                <div class="mt-6">
                    <h4 class="text-base font-medium text-blue-700 mb-3">要介護認定基準時間</h4>
                    <div class="relative h-10  border border-gray-300 shadow-sm">
                        <!-- 各要介護度の境界線 -->
                        <div class="absolute h-full w-px bg-gray-400 z-10" style="left: 21.3%"></div>
                        <div class="absolute h-full w-px bg-gray-400 z-10" style="left: 35.7%"></div>
                        <div class="absolute h-full w-px bg-gray-400 z-10" style="left: 50%"></div>
                        <div class="absolute h-full w-px bg-gray-400 z-10" style="left: 64.3%"></div>
                        <div class="absolute h-full w-px bg-gray-400 z-10" style="left: 78.6%"></div>
                        <div class="absolute h-full w-px bg-gray-400 z-10" style="left: 92.9%"></div>

                        <!-- 各行為区分の色分け表示 -->
                        @php
                            // CareTimeLogicサービスを利用して各行為区分の時間を取得
                            $careTimeLogic = new \App\Services\CareTimeLogic();

                            // 各行為区分の計算クラスを作成
                            $mealCalculator = new \App\Services\CareTimeCalculators\MealCareTimeCalculator();
                            $excretionCalculator = new \App\Services\CareTimeCalculators\ExcretionCareTimeCalculator();
                            $movementCalculator = new \App\Services\CareTimeCalculators\MovementCareTimeCalculator();
                            $hygieneCalculator = new \App\Services\CareTimeCalculators\HygieneCareTimeCalculator();
                            $indirectCalculator = new \App\Services\CareTimeCalculators\IndirectCareTimeCalculator();
                            $bpsdCalculator = new \App\Services\CareTimeCalculators\BPSDCareTimeCalculator();
                            $functionalTrainingCalculator = new \App\Services\CareTimeCalculators\FunctionalTrainingCareTimeCalculator();
                            $medicalCalculator = new \App\Services\CareTimeCalculators\MedicalCareTimeCalculator();

                            // 各行為区分の時間を計算
                            $mealTime = $mealCalculator->calculate($this->answers);
                            $excretionTime = $excretionCalculator->calculate($this->answers);
                            $movementTime = $movementCalculator->calculate($this->answers);
                            $hygieneTime = $hygieneCalculator->calculate($this->answers);
                            $indirectTime = $indirectCalculator->calculate($this->answers);
                            $bpsdTime = $bpsdCalculator->calculate($this->answers);
                            $functionalTrainingTime = $functionalTrainingCalculator->calculate($this->answers);
                            $medicalTime = $medicalCalculator->calculate($this->answers);

                            // 合計時間（最大170分とする）
                            $totalTime = $this->careTime;
                            $maxTime = 170;

                            // 各行為区分の幅を計算（%）
                            $mealWidth = ($mealTime / $maxTime) * 100;
                            $excretionWidth = ($excretionTime / $maxTime) * 100;
                            $movementWidth = ($movementTime / $maxTime) * 100;
                            $hygieneWidth = ($hygieneTime / $maxTime) * 100;
                            $indirectWidth = ($indirectTime / $maxTime) * 100;
                            $bpsdWidth = ($bpsdTime / $maxTime) * 100;
                            $functionalTrainingWidth = ($functionalTrainingTime / $maxTime) * 100;
                            $medicalWidth = ($medicalTime / $maxTime) * 100;

                            // 左端からの位置を計算
                            $excretionLeft = $mealWidth;
                            $movementLeft = $excretionLeft + $excretionWidth;
                            $hygieneLeft = $movementLeft + $movementWidth;
                            $indirectLeft = $hygieneLeft + $hygieneWidth;
                            $bpsdLeft = $indirectLeft + $indirectWidth;
                            $functionalTrainingLeft = $bpsdLeft + $bpsdWidth;
                            $medicalLeft = $functionalTrainingLeft + $functionalTrainingWidth;
                        @endphp

                        <!-- 各行為区分の色分け表示 -->
                        <div class="absolute h-10 bg-red-500" style="left: 0; width: {{ $mealWidth }}%"></div>
                        <div class="absolute h-10 bg-purple-500"
                            style="left: {{ $excretionLeft }}%; width: {{ $excretionWidth }}%"></div>
                        <div class="absolute h-10 bg-blue-500"
                            style="left: {{ $movementLeft }}%; width: {{ $movementWidth }}%"></div>
                        <div class="absolute h-10 bg-teal-500"
                            style="left: {{ $hygieneLeft }}%; width: {{ $hygieneWidth }}%"></div>
                        <div class="absolute h-10 bg-green-500"
                            style="left: {{ $indirectLeft }}%; width: {{ $indirectWidth }}%"></div>
                        <div class="absolute h-10 bg-yellow-500"
                            style="left: {{ $bpsdLeft }}%; width: {{ $bpsdWidth }}%"></div>
                        <div class="absolute h-10 bg-orange-500"
                            style="left: {{ $functionalTrainingLeft }}%; width: {{ $functionalTrainingWidth }}%">
                        </div>
                        <div class="absolute h-10 bg-lime-500"
                            style="left: {{ $medicalLeft }}%; width: {{ $medicalWidth }}%"></div>

                        <!-- 現在の時間を示すマーカー -->
                        @php
                            $position = min(($this->careTime / $maxTime) * 100, 100);
                        @endphp
                        <div class="absolute h-10 w-3 bg-black z-20" style="left: {{ $position }}%"></div>

                        <!-- 要介護度のラベル -->
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800" style="left: 10.7%">要支援1</div>
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800" style="left: 28.5%">要支援2</div>
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800" style="left: 42.9%">要介護1</div>
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800" style="left: 57.2%">要介護2</div>
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800" style="left: 71.5%">要介護3</div>
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800" style="left: 85.8%">要介護4</div>
                        <div class="absolute -bottom-6 text-sm font-medium text-blue-800 whitespace-nowrap"
                            style="left: 96.5%">要介護5</div>
                    </div>

                    <!-- 行為区分の凡例 -->
                    <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-red-500 mr-2 rounded"></div>
                            <span class="font-medium">食事: {{ number_format($mealTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-purple-500 mr-2 rounded"></div>
                            <span class="font-medium">排泄: {{ number_format($excretionTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-blue-500 mr-2 rounded"></div>
                            <span class="font-medium">移動: {{ number_format($movementTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-teal-500 mr-2 rounded"></div>
                            <span class="font-medium">清潔保持: {{ number_format($hygieneTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-green-500 mr-2 rounded"></div>
                            <span class="font-medium">間接: {{ number_format($indirectTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-yellow-500 mr-2 rounded"></div>
                            <span class="font-medium">BPSD関連: {{ number_format($bpsdTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-orange-500 mr-2 rounded"></div>
                            <span class="font-medium">機能訓練: {{ number_format($functionalTrainingTime, 1) }}分</span>
                        </div>
                        <div class="flex items-center gap-2 ">
                            <div class="w-5 h-5 bg-lime-500 mr-2 rounded"></div>
                            <span class="font-medium">医療関連: {{ number_format($medicalTime, 1) }}分</span>
                        </div>
                    </div>
                </div>

                <!-- 入力した調査項目と回答の表示 -->
                <div class="mt-10">
                    <h4 class="text-md font-medium text-gray-900 mb-4 p-2 bg-blue-100 rounded">入力内容の確認</h4>

                    @foreach ($this->groups as $groupId => $groupName)
                        <div class="mb-6">
                            <h5 class="font-medium text-gray-900 mb-2 p-1 bg-gray-100 rounded">{{ $groupName }}
                            </h5>

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
