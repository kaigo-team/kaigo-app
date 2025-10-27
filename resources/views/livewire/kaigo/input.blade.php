<?php

use function Livewire\Volt\{state, computed};

// 調査項目のグループとその内容を定義
state([
    'currentGroup' => 1,
    'answers' => [],
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
    'questions' => [
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
        '1-4' => [
            'title' => '起き上がり',
            'options' => ['つかまらないでできる', '何かにつかまればできる', 'できない'],
            'type' => 'radio',
        ],
        '1-5' => [
            'title' => '座位保持',
            'options' => ['できる', '自分の手で支えればできる', '支えてもらえればできる', 'できない'],
            'type' => 'radio',
        ],
        '1-6' => [
            'title' => '両足での立位',
            'options' => ['支えなしでできる', '何か支えがあればできる', 'できない'],
            'type' => 'radio',
        ],
        '1-7' => [
            'title' => '歩行',
            'options' => ['つかまらないでできる', '何かにつかまればできる', 'できない'],
            'type' => 'radio',
        ],
        '1-8' => [
            'title' => '立ち上がり',
            'options' => ['つかまらないでできる', '何かにつかまればできる', 'できない'],
            'type' => 'radio',
        ],
        '1-9' => [
            'title' => '片足での立位',
            'options' => ['支えなしでできる', '何か支えがあればできる', 'できない'],
            'type' => 'radio',
        ],
        '1-10' => [
            'title' => '洗身',
            'options' => ['自立（介助なし）', '一部介助', '全介助', '行っていない'],
            'type' => 'radio',
        ],
        '1-11' => [
            'title' => 'つめ切り',
            'options' => ['自立（介助なし）', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '1-12' => [
            'title' => '視力',
            'options' => ['普通（日常生活に支障がない）', '約1m離れた視力確認表の図が見える', '目の前においた視力確認表の図が見える', 'ほとんど見えない', 'みえているのか判断不能'],
            'type' => 'radio',
        ],
        '1-13' => [
            'title' => '聴力',
            'options' => ['普通', '普通の声がやっと聴き取れる', 'かなり大きな声ならなんとか聴き取れる', 'ほとんど聴こえない', '聴こえているのか判断不明'],
            'type' => 'radio',
        ],
        // 第2群
        '2-1' => [
            'title' => '移乗',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-2' => [
            'title' => '移動',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-3' => [
            'title' => '嚥下',
            'options' => ['できる', '見守り等', 'できない'],
            'type' => 'radio',
        ],
        '2-4' => [
            'title' => '食事摂取',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-5' => [
            'title' => '排尿',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-6' => [
            'title' => '排便',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-7' => [
            'title' => '口腔清潔',
            'options' => ['自立（介助なし）', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-8' => [
            'title' => '洗顔',
            'options' => ['自立（介助なし）', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-9' => [
            'title' => '整髪',
            'options' => ['自立（介助なし）', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-10' => [
            'title' => '上衣の着脱',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-11' => [
            'title' => 'ズボン等の着脱',
            'options' => ['自立（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '2-12' => [
            'title' => '外出頻度',
            'options' => ['週1回以上', '月1回以上', '月1回未満'],
            'type' => 'radio',
        ],
        // 第3群
        '3-1' => [
            'title' => '意思の伝達',
            'options' => ['調査対象者が意思を他者に伝達できる', 'ときどき伝達できる', 'ほとんど伝達できない', 'できない'],
            'type' => 'radio',
        ],
        '3-2' => [
            'title' => '毎日の日課を理解',
            'options' => ['できる', 'できない'],
            'type' => 'radio',
        ],
        '3-3' => [
            'title' => '生年月日を言う',
            'options' => ['できる', 'できない'],
            'type' => 'radio',
        ],
        '3-4' => [
            'title' => '短期記憶',
            'options' => ['できる', 'できない'],
            'type' => 'radio',
        ],
        '3-5' => [
            'title' => '自分の名前を言う',
            'options' => ['できる', 'できない'],
            'type' => 'radio',
        ],
        '3-6' => [
            'title' => '今の季節を理解',
            'options' => ['できる', 'できない'],
            'type' => 'radio',
        ],
        '3-7' => [
            'title' => '場所の理解',
            'options' => ['できる', 'できない'],
            'type' => 'radio',
        ],
        '3-8' => [
            'title' => '常時の徘徊',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '3-9' => [
            'title' => '外出して戻れない',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        // 第4群
        '4-1' => [
            'title' => '被害的',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-2' => [
            'title' => '作話',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-3' => [
            'title' => '感情が不安定',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-4' => [
            'title' => '昼夜逆転',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-5' => [
            'title' => '同じ話をする',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-6' => [
            'title' => '大声をだす',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-7' => [
            'title' => '介護に抵抗',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-8' => [
            'title' => '落ち着きなし',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-9' => [
            'title' => '一人で出たがる',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-10' => [
            'title' => '収集癖',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-11' => [
            'title' => '物や衣類を隠す',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-12' => [
            'title' => 'ひどい物忘れ',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-13' => [
            'title' => '独り言・独り笑い',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-14' => [
            'title' => '自分勝手に行動する',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '4-15' => [
            'title' => '話がまとまらない',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        // 第5群
        '5-1' => [
            'title' => '薬の内服',
            'options' => ['自立（介助なし）', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '5-2' => [
            'title' => '金銭の管理',
            'options' => ['自立（介助なし）', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '5-3' => [
            'title' => '日常の意思決定',
            'options' => ['できる', '特別な場合を除いてできる', '日常的に困難', 'できない'],
            'type' => 'radio',
        ],
        '5-4' => [
            'title' => '集団参加ができない',
            'options' => ['ない', 'ときどきある', 'ある'],
            'type' => 'radio',
        ],
        '5-5' => [
            'title' => '買い物',
            'options' => ['できる（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        '5-6' => [
            'title' => '簡単な調理',
            'options' => ['できる（介助なし）', '見守り等', '一部介助', '全介助'],
            'type' => 'radio',
        ],
        // 第6群
        '6-1' => [
            'title' => '処置内容',
            'options' => ['点滴の管理', '中心静脈栄養', '透析', 'ストーマ（人工肛門）の処置', '酸素両方', 'レスピレーター（人工呼吸器）', '気管切開の処置', '疼痛の看護', '経管栄養'],
            'type' => 'checkbox',
        ],
        '6-2' => [
            'title' => '特別な医療行為',
            'options' => ['モニター測定（血圧・心拍・酸素飽和度等）', 'じょくそうの処置', 'カテーテル（コンドームカテーテル・留置カテーテル・ウロストーマ等）'],
            'type' => 'checkbox',
        ],
        // 第7群
        '7-1' => [
            'title' => '障害高齢者自立度',
            'options' => ['自立', 'J1', 'J2', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'],
            'type' => 'radio',
        ],
        '7-2' => [
            'title' => '認知症高齢者自立度',
            'options' => ['自立', 'Ⅰ', 'Ⅱa', 'Ⅱb', 'Ⅲa', 'Ⅲb', 'Ⅳ', 'M'],
            'type' => 'radio',
        ],
        // 第8群
        '8-1' => [
            'title' => '日常の意思決定を行うための認知能力',
            'options' => ['自立', 'いくらか困難', '見守りが必要', '判断できない'],
            'type' => 'radio',
        ],
        '8-2' => [
            'title' => '自分の意思の伝達力',
            'options' => ['自立', 'いくらか困難', '具体的要求に限られる', '伝えられない'],
            'type' => 'radio',
        ],
        '8-3' => [
            'title' => '短期記憶',
            'options' => ['問題なし', '問題あり'],
            'type' => 'radio',
        ],
        '8-4' => [
            'title' => '食事自己動作',
            'options' => ['自立', 'なんとか自分で食べられる', '全面介助'],
            'type' => 'radio',
        ],
        '8-5' => [
            'title' => '認知症高齢者の日常生活自立度',
            'options' => ['自立', 'Ⅰ', 'Ⅱa', 'Ⅱb', 'Ⅲa', 'Ⅳ', 'M', '記載無し'],
            'type' => 'radio',
        ],
    ],
]);

// 現在のグループに属する質問を取得
$currentGroupQuestions = computed(function () {
    $groupPrefix = $this->currentGroup . '-';
    $questions = [];

    foreach ($this->questions as $key => $question) {
        if (str_starts_with($key, $groupPrefix)) {
            $questions[$key] = $question;
        }
    }

    return $questions;
});

// 回答の進捗率を計算
$progressPercentage = computed(function () {
    $totalQuestions = count($this->questions);
    $answeredQuestions = count($this->answers);

    return ($answeredQuestions / $totalQuestions) * 100;
});

// 質問に回答する
$answerQuestion = function ($questionId, $answer, $isCheckbox = false) {
    if ($isCheckbox) {
        if (!isset($this->answers[$questionId])) {
            $this->answers[$questionId] = [];
        }

        $index = array_search($answer, $this->answers[$questionId]);
        if ($index !== false) {
            unset($this->answers[$questionId][$index]);
            $this->answers[$questionId] = array_values($this->answers[$questionId]);
        } else {
            $this->answers[$questionId][] = $answer;
        }
    } else {
        $this->answers[$questionId] = $answer;
    }
};

// 前のグループに移動
$previousGroup = function () {
    if ($this->currentGroup > 1) {
        $this->currentGroup--;
    }
};

// 次のグループに移動
$nextGroup = function () {
    if ($this->currentGroup < count($this->groups)) {
        $this->currentGroup++;
    }
};

// 結果画面に移動
$showResult = function () {
    // 回答をJSONに変換して結果画面に渡す
    $answersJson = urlencode(json_encode($this->answers));
    $this->redirect(route('kaigo.result', ['input' => $answersJson]));
};

?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">
                    要介護度算出アプリ
                </h2>

                <!-- 進捗バー -->
                <div class="w-full bg-gray-200 rounded-full h-2.5 my-4">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $this->progressPercentage }}%"></div>
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
