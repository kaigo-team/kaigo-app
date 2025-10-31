<?php

namespace App\Services\CareTimeCalculators;

use App\Services\BaseCareTimeCalculator;

/**
 * BPSD関連行為に関する要介護認定基準時間を算出するクラス
 * 時間の表示範囲：5.8～21.2分
 */
class BPSDCareTimeCalculator extends BaseCareTimeCalculator
{
    /**
     * BPSD関連行為に関する要介護認定基準時間を算出する
     * 
     * @param array $answers 回答データ
     * @return float BPSD関連行為に関する時間（分）
     */
    public function calculate(array $answers): float
    {
        // 精神・行動障害の中間得点を計算
        $mentalScore = $this->calculateMentalBehaviorDisorderScore($answers);

        if ($mentalScore <= 81.0) {
            return $this->calculateForLowMentalScore($answers);
        } else {
            return $this->calculateForHighMentalScore($answers, $mentalScore);
        }
    }

    /**
     * 精神・行動障害の中間得点が81.0以下の場合のBPSD関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @return float BPSD関連行為時間（分）
     */
    private function calculateForLowMentalScore(array $answers): float
    {
        // 項目1-1麻痺の回答をチェック
        if (!isset($answers['1-1'])) {
            return 5.8;
        }

        $paralysisAnswer = $answers['1-1'];

        if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
            // 項目3-1意思の伝達の回答をチェック
            if (!isset($answers['3-1'])) {
                return 5.8;
            }

            $communicationAnswer = $answers['3-1'];

            if (
                $communicationAnswer === '調査対象者が意思を他者に伝達できる' ||
                $communicationAnswer === 'ときどき伝達できる' ||
                $communicationAnswer === 'ほとんど伝達できない'
            ) {
                // 項目1-7歩行の回答をチェック
                if (!isset($answers['1-7'])) {
                    return 5.8;
                }

                $walkingAnswer = $answers['1-7'];

                if ($walkingAnswer === 'つかまらないでできる') {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 90.2) {
                        return 16.1;
                    } else {
                        return 10.5;
                    }
                } else {
                    // 項目1-5座位保持の回答をチェック
                    if (!isset($answers['1-5'])) {
                        return 5.8;
                    }

                    $sittingAnswer = $answers['1-5'];

                    if ($sittingAnswer === 'できる') {
                        return 10.6;
                    } else {
                        return 7.6;
                    }
                }
            } else {
                // 項目3-1意思の伝達が「できない」の場合
                return 21.2;
            }
        } else {
            // 項目1-1麻痺がある場合
            // 項目3-9外出すると戻れないことの回答をチェック
            if (!isset($answers['3-9'])) {
                return 5.8;
            }

            $wanderingAnswer = $answers['3-9'];

            if ($wanderingAnswer === 'ない') {
                // 身体機能・起居動作の中間得点を計算
                $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                if ($physicalScore <= 48.6) {
                    // 項目5-3日常の意思決定の回答をチェック
                    if (!isset($answers['5-3'])) {
                        return 5.8;
                    }

                    $decisionAnswer = $answers['5-3'];

                    if (
                        $decisionAnswer === 'できる' ||
                        $decisionAnswer === '特別な場合を除いてできる' ||
                        $decisionAnswer === '日常的に困難'
                    ) {
                        return 6.7;
                    } else {
                        return 8.1;
                    }
                } else {
                    return 9.0;
                }
            } else {
                // 項目3-9外出すると戻れないことが「ときどきある」or「ある」の場合
                return 10.8;
            }
        }
    }

    /**
     * 精神・行動障害の中間得点が81.1以上の場合のBPSD関連行為時間を計算
     * 
     * @param array $answers 回答データ
     * @param float $mentalScore 精神・行動障害の中間得点
     * @return float BPSD関連行為時間（分）
     */
    private function calculateForHighMentalScore(array $answers, float $mentalScore): float
    {
        if ($mentalScore <= 90.8) {
            // 項目3-1意思の伝達の回答をチェック
            if (!isset($answers['3-1'])) {
                return 5.8;
            }

            $communicationAnswer = $answers['3-1'];

            if (
                $communicationAnswer === '調査対象者が意思を他者に伝達できる' ||
                $communicationAnswer === 'ときどき伝達できる' ||
                $communicationAnswer === 'ほとんど伝達できない'
            ) {
                // 項目3-8徘徊の回答をチェック
                if (!isset($answers['3-8'])) {
                    return 5.8;
                }

                $wanderingAnswer = $answers['3-8'];

                if ($wanderingAnswer === 'ない') {
                    // 身体機能・起居動作の中間得点を計算
                    $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                    if ($physicalScore <= 68.1) {
                        return 6.3;
                    } else {
                        // 認知機能の中間得点を計算
                        $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                        if ($cognitiveScore <= 80.5) {
                            return 7.5;
                        } else {
                            return 6.2;
                        }
                    }
                } else {
                    // 項目3-8徘徊が「ときどきある」or「ある」の場合
                    return 8.7;
                }
            } else {
                // 項目3-1意思の伝達が「できない」の場合
                return 10.1;
            }
        } else {
            // 精神・行動障害の中間得点が90.9以上の場合
            if ($mentalScore <= 95.3) {
                // 項目1-1麻痺の回答をチェック
                if (!isset($answers['1-1'])) {
                    return 5.8;
                }

                $paralysisAnswer = $answers['1-1'];

                if (empty($paralysisAnswer) || (is_array($paralysisAnswer) && empty($paralysisAnswer))) {
                    // 認知機能の中間得点を計算
                    $cognitiveScore = $this->calculateCognitiveFunctionScore($answers);

                    if ($cognitiveScore <= 75.8) {
                        return 7.6;
                    } else {
                        return 6.4;
                    }
                } else {
                    return 6.2;
                }
            } else {
                // 精神・行動障害の中間得点が95.4以上の場合
                // 身体機能・起居動作の中間得点を計算
                $physicalScore = $this->calculatePhysicalFunctionScore($answers);

                if ($physicalScore <= 18.3) {
                    return 5.8;
                } else {
                    // 項目2-7口腔清潔の回答をチェック
                    if (!isset($answers['2-7'])) {
                        return 5.8;
                    }

                    $oralCareAnswer = $answers['2-7'];

                    if ($oralCareAnswer === '自立（介助なし）') {
                        return 5.8;
                    } else {
                        // 項目1-7歩行の回答をチェック
                        if (!isset($answers['1-7'])) {
                            return 5.8;
                        }

                        $walkingAnswer = $answers['1-7'];

                        if ($walkingAnswer === 'つかまらないでできる' || $walkingAnswer === '何かにつかまればできる') {
                            return 6.4;
                        } else {
                            return 6.1;
                        }
                    }
                }
            }
        }
    }
}
