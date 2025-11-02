<?php

namespace App\Services\CareTimeCalculators;

use App\Services\BaseCareTimeCalculator;

/**
 * 医療関連行為に関する要介護認定基準時間を算出するクラス
 * 時間の表示範囲：1.0～37.2分
 */
class MedicalCareTimeCalculator extends BaseCareTimeCalculator
{
    /**
     * 医療関連行為に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為に関する時間（分）
     */
    public function calculate(array $answers): float
    {
        // 基本的な医療関連行為時間を計算
        $baseTime = $this->calculateBaseMedicalTime($answers);

        // 特別な医療行為の加算時間を計算
        $additionalTime = $this->calculateSpecialMedicalCareTime($answers);

        // 基本時間と加算時間を合計
        return $baseTime + $additionalTime;
    }

    /**
     * 基本的な医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 基本的な医療関連行為時間（分）
     */
    private function calculateBaseMedicalTime(array $answers): float
    {
        // 項目2-3えん下の回答をチェック
        if (!isset($answers['2-3'])) {
            return 1.0;
        }

        $swallowingAnswer = $answers['2-3'];

        if ($swallowingAnswer === 'できる' || $swallowingAnswer === 'できるが不安') {
            return $this->calculateForSwallowing($answers);
        } else {
            // 項目2-3えん下が「できない」の場合
            return $this->calculateForNoSwallowing($answers);
        }
    }

    /**
     * 特別な医療行為の加算時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 特別な医療行為の加算時間（分）
     */
    private function calculateSpecialMedicalCareTime(array $answers): float
    {
        $additionalTime = 0.0;

        // 特別な医療行為の加算時間マップ
        $specialMedicalCareMap = [
            // 処置内容（6-1）
            '点滴の管理' => 8.5,
            '中心静脈栄養' => 8.5,
            '透析' => 8.5,
            'ストーマ（人工肛門）の処置' => 3.8,
            '酸素両方' => 0.8,
            'レスピレーター（人工呼吸器）' => 4.5,
            '気管切開の処置' => 5.6,
            '疼痛の看護' => 2.1,
            '経管栄養' => 9.1,
            // 特別な対応（6-2）
            'モニター測定（血圧・心拍・酸素飽和度等）' => 3.6,
            'じょくそうの処置' => 4.0,
            'カテーテル（コンドームカテーテル・留置カテーテル・ウロストーマ等）' => 8.2,
        ];

        // 項目6-1処置内容の回答をチェック
        if (isset($answers['6-1'])) {
            $treatmentAnswers = is_array($answers['6-1']) ? $answers['6-1'] : [$answers['6-1']];

            foreach ($treatmentAnswers as $treatmentAnswer) {
                if (isset($specialMedicalCareMap[$treatmentAnswer])) {
                    $additionalTime += $specialMedicalCareMap[$treatmentAnswer];
                }
            }
        }

        // 項目6-2特別な医療行為の回答をチェック
        if (isset($answers['6-2'])) {
            $specialCareAnswers = is_array($answers['6-2']) ? $answers['6-2'] : [$answers['6-2']];

            foreach ($specialCareAnswers as $specialCareAnswer) {
                if (isset($specialMedicalCareMap[$specialCareAnswer])) {
                    $additionalTime += $specialMedicalCareMap[$specialCareAnswer];
                }
            }
        }

        return $additionalTime;
    }

    /**
     * えん下ができる・見守り等の場合の医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為時間（分）
     */
    private function calculateForSwallowing(array $answers): float
    {
        // 項目2-2移動の回答をチェック
        if (!isset($answers['2-2'])) {
            return 1.0;
        }

        $movementAnswer = $answers['2-2'];

        if ($movementAnswer === 'できる' || $movementAnswer === 'できるが不安') {
            return $this->calculateForIndependentMovement($answers);
        } else {
            // 項目2-2移動が「一部介助」or「全介助」の場合
            return $this->calculateForAssistedMovement($answers);
        }
    }

    /**
     * 移動が自立・見守り等の場合の医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為時間（分）
     */
    private function calculateForIndependentMovement(array $answers): float
    {
        // 項目2-1移乗の回答をチェック
        if (!isset($answers['2-1'])) {
            return 1.0;
        }

        $transferAnswer = $answers['2-1'];

        if ($transferAnswer === 'できる') {
            // 項目1-8立ち上がりの回答をチェック
            if (!isset($answers['1-8'])) {
                return 1.0;
            }

            $standingAnswer = $answers['1-8'];

            if ($standingAnswer === 'つかまらないでできる') {
                return 1.0;
            } else {
                // 項目2-12外出頻度の回答をチェック
                if (!isset($answers['2-12'])) {
                    return 1.0;
                }

                $outingAnswer = $answers['2-12'];

                if ($outingAnswer === '週1回以上ある' || $outingAnswer === '月1回以上ある') {
                    return 4.2;
                } else {
                    // 社会生活への適応の中間得点を計算
                    $socialScore = $this->calculateSocialAdaptationScore($answers);

                    if ($socialScore <= 19.5) {
                        return 3.3;
                    } else {
                        return 2.0;
                    }
                }
            }
        } else {
            // 項目2-1移乗が「見守り等」or「一部介助」or「全介助」の場合
            // 精神・行動障害の中間得点を計算
            $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

            if ($mentalScore <= 66.8) {
                return 6.0;
            } else {
                // 項目3-7場所の理解の回答をチェック
                if (!isset($answers['3-7'])) {
                    return 1.0;
                }

                $placeAnswer = $answers['3-7'];

                if ($placeAnswer === 'できる') {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 76.0) {
                        // 生活機能の中間得点を計算
                        $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                        if ($lifeFunctionScore <= 72.2) {
                            return 4.5;
                        } else {
                            return 3.2;
                        }
                    } else {
                        return 5.9;
                    }
                } else {
                    // 項目3-7場所の理解が「できない」の場合
                    return 2.6;
                }
            }
        }
    }

    /**
     * 移動が一部介助・全介助の場合の医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為時間（分）
     */
    private function calculateForAssistedMovement(array $answers): float
    {
        // 項目1-7歩行の回答をチェック
        if (!isset($answers['1-7'])) {
            return 1.0;
        }

        $walkingAnswer = $answers['1-7'];

        if ($walkingAnswer === 'つかまらないでできる' || $walkingAnswer === '何かにつかまればできる') {
            // 認知機能の中間得点を計算
            $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

            if ($cognitiveScore <= 52.7) {
                return 3.0;
            } else {
                // 項目1-9片足での立位保持の回答をチェック
                if (!isset($answers['1-9'])) {
                    return 1.0;
                }

                $singleLegStandingAnswer = $answers['1-9'];

                if ($singleLegStandingAnswer === 'つかまらないでできる' || $singleLegStandingAnswer === '何かにつかまればできる') {
                    return 4.4;
                } else {
                    return 7.4;
                }
            }
        } else {
            // 項目1-7歩行が「できない」の場合
            // 項目1-1麻痺の回答をチェック
            if (!isset($answers['1-1'])) {
                return $this->calculateForNoParalysis($answers);
            }

            $paralysisAnswer = $answers['1-1'];

            if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
                return $this->calculateForNoParalysis($answers);
            } else {
                return $this->calculateForParalysis($answers);
            }
        }
    }

    /**
     * 麻痺がない場合の医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為時間（分）
     */
    private function calculateForNoParalysis(array $answers): float
    {
        // 項目3-1意思の伝達の回答をチェック
        if (!isset($answers['3-1'])) {
            return 1.0;
        }

        $communicationAnswer = $answers['3-1'];

        if (
            $communicationAnswer === '意思を伝達できる' ||
            $communicationAnswer === 'ときどき伝達できる'
        ) {
            // 生活機能の中間得点を計算
            $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

            if ($lifeFunctionScore <= 26.0) {
                return 14.8;
            } else {
                return 10.1;
            }
        } else {
            // 項目3-1意思の伝達が「ほとんど伝達できない」or「できない」の場合
            return 7.0;
        }
    }

    /**
     * 麻痺がある場合の医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為時間（分）
     */
    private function calculateForParalysis(array $answers): float
    {
        // 身体機能・起居動作の中間得点を計算
        $physicalScore = $this->calculatePhysicalFunctionScore($answers);

        if ($physicalScore <= 16.4) {
            return 8.3;
        } else {
            // 項目3-2毎日の日課を理解することの回答をチェック
            if (!isset($answers['3-2'])) {
                return 1.0;
            }

            $routineAnswer = $answers['3-2'];

            if ($routineAnswer === 'できる') {
                // 生活機能の中間得点を計算
                $lifeFunctionScore = $this->calculateLifeFunctionScore($answers);

                if ($lifeFunctionScore <= 41.5) {
                    return 9.2;
                } else {
                    return 5.1;
                }
            } else {
                // 項目3-2毎日の日課を理解することが「できない」の場合
                // 項目3-5自分の名前を言うことの回答をチェック
                if (!isset($answers['3-5'])) {
                    return 1.0;
                }

                $nameAnswer = $answers['3-5'];

                if ($nameAnswer === 'できる') {
                    // 項目2-4食事摂取の回答をチェック
                    if (!isset($answers['2-4'])) {
                        return 1.0;
                    }

                    $mealAnswer = $answers['2-4'];

                    if (
                        $mealAnswer === 'できる' ||
                        $mealAnswer === 'できるが不安' ||
                        $mealAnswer === '少し手を借りればできる'
                    ) {
                        // 精神・行動障害の中間得点を計算
                        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

                        if ($mentalScore <= 97.3) {
                            if ($mentalScore <= 84.6) {
                                return 3.9;
                            } else {
                                return 5.3;
                            }
                        } else {
                            return 2.9;
                        }
                    } else {
                        // 項目2-4食事摂取が「全介助」の場合
                        return 6.1;
                    }
                } else {
                    // 項目3-5自分の名前を言うことが「できない」の場合
                    return 6.5;
                }
            }
        }
    }

    /**
     * えん下ができない場合の医療関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float 医療関連行為時間（分）
     */
    private function calculateForNoSwallowing(array $answers): float
    {
        // 項目1-12視力の回答をチェック
        if (!isset($answers['1-12'])) {
            return 1.0;
        }

        $visionAnswer = $answers['1-12'];

        if (
            $visionAnswer === '生活に支障がない' ||
            $visionAnswer === '約1m離れた視力確認表の図が見える'
        ) {
            // 精神・行動障害の中間得点を計算
            $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

            if ($mentalScore <= 95.8) {
                return 28.0;
            } else {
                return 29.0;
            }
        } else {
            // 項目1-12視力が「目の前においた視力確認表の図が見える」or「ほとんど見えない」or「みえているのか判断不能」の場合
            // 身体機能・起居動作の中間得点を計算
            $physicalScore = $this->calculatePhysicalFunctionScore($answers);

            if ($physicalScore <= 10.1) {
                if ($physicalScore <= 0.5) {
                    return 32.0;
                } else {
                    return 33.7;
                }
            } else {
                return 37.2;
            }
        }
    }
}
