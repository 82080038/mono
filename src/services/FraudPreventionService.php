<?php
/**
 * Fraud Prevention Service - SaaS Koperasi Harian
 * 
 * Advanced fraud detection and prevention system with
 * machine learning algorithms and real-time monitoring
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class FraudPreventionService
{
    private $db;
    private $mlModels;
    private $riskThresholds;
    private $alertSystem;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->initializeMLModels();
        $this->loadRiskThresholds();
        $this->alertSystem = new AlertSystem();
    }
    
    /**
     * Real-time Fraud Detection with ML
     */
    public function detectFraudRealTime(array $transactionData): array
    {
        try {
            $detection = [
                'is_fraud' => false,
                'confidence' => 0,
                'risk_score' => 0,
                'risk_factors' => [],
                'ml_prediction' => [],
                'recommendations' => [],
                'should_block' => false,
                'requires_review' => false
            ];
            
            // Extract features for ML model
            $features = $this->extractTransactionFeatures($transactionData);
            
            // Run through multiple ML models
            $modelPredictions = [
                'isolation_forest' => $this->runIsolationForest($features),
                'random_forest' => $this->runRandomForest($features),
                'neural_network' => $this->runNeuralNetwork($features),
                'gradient_boosting' => $this->runGradientBoosting($features)
            ];
            
            // Ensemble prediction (weighted average)
            $ensembleScore = $this->calculateEnsembleScore($modelPredictions);
            $detection['ml_prediction'] = $modelPredictions;
            $detection['confidence'] = $ensembleScore;
            
            // Traditional rule-based detection
            $ruleBasedRisk = $this->ruleBasedDetection($transactionData);
            $detection['risk_factors'] = $ruleBasedRisk['factors'];
            
            // Combine ML and rule-based results
            $combinedScore = ($ensembleScore * 0.7) + ($ruleBasedRisk['score'] * 0.3);
            $detection['risk_score'] = $combinedScore;
            
            // Determine fraud probability
            $detection['is_fraud'] = $combinedScore > $this->riskThresholds['fraud_threshold'];
            $detection['should_block'] = $combinedScore > $this->riskThresholds['block_threshold'];
            $detection['requires_review'] = $combinedScore > $this->riskThresholds['review_threshold'];
            
            // Generate recommendations
            $detection['recommendations'] = $this->generateFraudRecommendations($detection, $transactionData);
            
            // Log detection results
            $this->logFraudDetection($transactionData, $detection);
            
            // Send alerts if high risk
            if ($detection['risk_score'] > $this->riskThresholds['alert_threshold']) {
                $this->sendFraudAlert($transactionData, $detection);
            }
            
            return $detection;
            
        } catch (Exception $e) {
            throw new Exception('Real-time fraud detection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Behavioral Pattern Analysis
     */
    public function analyzeBehavioralPatterns(int $userId, array $recentTransactions): array
    {
        try {
            $analysis = [
                'user_id' => $userId,
                'anomaly_score' => 0,
                'behavioral_changes' => [],
                'risk_indicators' => [],
                'patterns' => [],
                'recommendations' => []
            ];
            
            // Get user's historical behavior baseline
            $baseline = $this->getUserBehaviorBaseline($userId);
            
            if (empty($baseline)) {
                // Establish baseline for new user
                $this->establishUserBaseline($userId, $recentTransactions);
                $analysis['recommendations'][] = 'Behavioral baseline established for new user';
                return $analysis;
            }
            
            // Analyze recent behavior against baseline
            $behavioralChanges = [
                'transaction_amounts' => $this->analyzeAmountPatterns($baseline, $recentTransactions),
                'transaction_frequency' => $this->analyzeFrequencyPatterns($baseline, $recentTransactions),
                'transaction_timing' => $this->analyzeTimingPatterns($baseline, $recentTransactions),
                'location_patterns' => $this->analyzeLocationPatterns($baseline, $recentTransactions),
                'device_patterns' => $this->analyzeDevicePatterns($baseline, $recentTransactions),
                'merchant_patterns' => $this->analyzeMerchantPatterns($baseline, $recentTransactions)
            ];
            
            // Calculate overall anomaly score
            $totalAnomalies = 0;
            $totalChecks = 0;
            
            foreach ($behavioralChanges as $changeType => $changeData) {
                if ($changeData['is_anomaly']) {
                    $totalAnomalies += $changeData['severity'];
                    $analysis['behavioral_changes'][] = [
                        'type' => $changeType,
                        'severity' => $changeData['severity'],
                        'details' => $changeData['details']
                    ];
                }
                $totalChecks++;
            }
            
            $analysis['anomaly_score'] = $totalChecks > 0 ? ($totalAnomalies / $totalChecks) : 0;
            
            // Identify specific risk indicators
            $analysis['risk_indicators'] = $this->identifyRiskIndicators($behavioralChanges);
            
            // Extract current patterns
            $analysis['patterns'] = $this->extractCurrentPatterns($recentTransactions);
            
            // Generate recommendations
            $analysis['recommendations'] = $this->generateBehavioralRecommendations($analysis);
            
            // Update user baseline if no anomalies detected
            if ($analysis['anomaly_score'] < $this->riskThresholds['baseline_update_threshold']) {
                $this->updateUserBaseline($userId, $recentTransactions);
            }
            
            return $analysis;
            
        } catch (Exception $e) {
            throw new Exception('Behavioral pattern analysis failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Network Analysis for Fraud Rings
     */
    public function analyzeFraudNetworks(): array
    {
        try {
            $networkAnalysis = [
                'suspicious_networks' => [],
                'connected_accounts' => [],
                'risk_clusters' => [],
                'recommendations' => []
            ];
            
            // Build transaction network graph
            $networkGraph = $this->buildTransactionNetwork();
            
            // Detect suspicious clusters using community detection
            $clusters = $this->detectSuspiciousClusters($networkGraph);
            
            foreach ($clusters as $cluster) {
                $clusterAnalysis = $this->analyzeCluster($cluster);
                
                if ($clusterAnalysis['risk_score'] > $this->riskThresholds['cluster_threshold']) {
                    $networkAnalysis['suspicious_networks'][] = $clusterAnalysis;
                    $networkAnalysis['risk_clusters'][] = $cluster['nodes'];
                }
            }
            
            // Identify connected high-risk accounts
            $networkAnalysis['connected_accounts'] = $this->findConnectedHighRiskAccounts($networkGraph);
            
            // Generate network-level recommendations
            $networkAnalysis['recommendations'] = $this->generateNetworkRecommendations($networkAnalysis);
            
            return $networkAnalysis;
            
        } catch (Exception $e) {
            throw new Exception('Fraud network analysis failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Adaptive Risk Scoring
     */
    public function calculateAdaptiveRiskScore(array $transactionData, int $userId): array
    {
        try {
            $riskScoring = [
                'base_score' => 0,
                'adaptive_score' => 0,
                'risk_factors' => [],
                'adjustments' => [],
                'final_score' => 0,
                'risk_level' => 'low'
            ];
            
            // Calculate base risk score using traditional methods
            $baseScore = $this->calculateBaseRiskScore($transactionData);
            $riskScoring['base_score'] = $baseScore;
            
            // Get user's risk profile
            $userRiskProfile = $this->getUserRiskProfile($userId);
            
            // Apply adaptive adjustments based on user behavior
            $adjustments = [
                'user_history' => $this->getUserHistoryAdjustment($userRiskProfile),
                'recent_activity' => $this->getRecentActivityAdjustment($userId),
                'seasonal_patterns' => $this->getSeasonalAdjustment($transactionData),
                'market_conditions' => $this->getMarketConditionAdjustment(),
                'device_trust' => $this->getDeviceTrustAdjustment($transactionData),
                'location_trust' => $this->getLocationTrustAdjustment($transactionData)
            ];
            
            $riskScoring['adjustments'] = $adjustments;
            
            // Calculate adaptive score
            $adaptiveScore = $baseScore;
            foreach ($adjustments as $adjustmentType => $adjustment) {
                $adaptiveScore *= $adjustment['multiplier'];
                $riskScoring['risk_factors'][] = [
                    'type' => $adjustmentType,
                    'multiplier' => $adjustment['multiplier'],
                    'reason' => $adjustment['reason']
                ];
            }
            
            $riskScoring['adaptive_score'] = max(0, min(1, $adaptiveScore));
            $riskScoring['final_score'] = $riskScoring['adaptive_score'];
            $riskScoring['risk_level'] = $this->determineRiskLevel($riskScoring['final_score']);
            
            // Update user risk profile based on this transaction
            $this->updateUserRiskProfile($userId, $transactionData, $riskScoring);
            
            return $riskScoring;
            
        } catch (Exception $e) {
            throw new Exception('Adaptive risk scoring failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Fraud Prevention Dashboard
     */
    public function getFraudPreventionDashboard(): array
    {
        try {
            $dashboard = [
                'overview' => [],
                'real_time_alerts' => [],
                'trending_patterns' => [],
                'prevention_metrics' => [],
                'action_items' => []
            ];
            
            // Get overview statistics
            $dashboard['overview'] = [
                'total_transactions_today' => $this->getTransactionCount('today'),
                'fraud_attempts_blocked' => $this->getBlockedFraudCount('today'),
                'fraud_detection_rate' => $this->getFraudDetectionRate('today'),
                'false_positive_rate' => $this->getFalsePositiveRate('today'),
                'average_risk_score' => $this->getAverageRiskScore('today'),
                'high_risk_transactions' => $this->getHighRiskTransactionCount('today')
            ];
            
            // Get real-time alerts
            $dashboard['real_time_alerts'] = $this->getRealTimeFraudAlerts();
            
            // Get trending fraud patterns
            $dashboard['trending_patterns'] = $this->getTrendingFraudPatterns();
            
            // Get prevention metrics
            $dashboard['prevention_metrics'] = [
                'ml_model_accuracy' => $this->getMLModelAccuracy(),
                'rule_effectiveness' => $this->getRuleEffectiveness(),
                'prevention_savings' => $this->getPreventionSavings(),
                'detection_speed' => $this->getAverageDetectionSpeed()
            ];
            
            // Get action items
            $dashboard['action_items'] = $this->getFraudPreventionActionItems();
            
            return $dashboard;
            
        } catch (Exception $e) {
            throw new Exception('Fraud prevention dashboard failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function initializeMLModels(): void
    {
        $this->mlModels = [
            'isolation_forest' => [
                'model_path' => '/models/isolation_forest.pkl',
                'threshold' => 0.5,
                'features' => ['amount', 'time_of_day', 'day_of_week', 'location_distance', 'device_trust']
            ],
            'random_forest' => [
                'model_path' => '/models/random_forest.pkl',
                'threshold' => 0.6,
                'features' => ['amount', 'frequency', 'merchant_risk', 'location_risk', 'device_risk']
            ],
            'neural_network' => [
                'model_path' => '/models/neural_network.pkl',
                'threshold' => 0.7,
                'features' => ['amount', 'time', 'location', 'device', 'user_behavior']
            ],
            'gradient_boosting' => [
                'model_path' => '/models/gradient_boosting.pkl',
                'threshold' => 0.65,
                'features' => ['amount', 'frequency', 'timing', 'location', 'merchant']
            ]
        ];
    }
    
    private function loadRiskThresholds(): void
    {
        $this->riskThresholds = [
            'fraud_threshold' => 0.7,
            'block_threshold' => 0.85,
            'review_threshold' => 0.6,
            'alert_threshold' => 0.5,
            'baseline_update_threshold' => 0.2,
            'cluster_threshold' => 0.8
        ];
    }
    
    private function extractTransactionFeatures(array $transactionData): array
    {
        return [
            'amount' => $transactionData['amount'] ?? 0,
            'time_of_day' => (int)date('H', $transactionData['timestamp'] ?? time()),
            'day_of_week' => (int)date('w', $transactionData['timestamp'] ?? time()),
            'location_distance' => $this->calculateLocationDistance($transactionData),
            'device_trust' => $this->getDeviceTrustScore($transactionData),
            'merchant_risk' => $this->getMerchantRiskScore($transactionData),
            'user_risk' => $this->getUserRiskScore($transactionData['user_id'] ?? 0),
            'frequency_score' => $this->getFrequencyScore($transactionData)
        ];
    }
    
    private function runIsolationForest(array $features): array
    {
        // Placeholder for ML model inference
        // In production, this would load and run the actual model
        return [
            'prediction' => rand(0, 1),
            'confidence' => rand(0.5, 1.0),
            'anomaly_score' => rand(0, 1)
        ];
    }
    
    private function runRandomForest(array $features): array
    {
        return [
            'prediction' => rand(0, 1),
            'confidence' => rand(0.5, 1.0),
            'probability' => rand(0, 1)
        ];
    }
    
    private function runNeuralNetwork(array $features): array
    {
        return [
            'prediction' => rand(0, 1),
            'confidence' => rand(0.5, 1.0),
            'activation' => rand(0, 1)
        ];
    }
    
    private function runGradientBoosting(array $features): array
    {
        return [
            'prediction' => rand(0, 1),
            'confidence' => rand(0.5, 1.0),
            'score' => rand(0, 1)
        ];
    }
    
    private function calculateEnsembleScore(array $predictions): float
    {
        $totalScore = 0;
        $totalWeight = 0;
        
        $weights = [
            'isolation_forest' => 0.3,
            'random_forest' => 0.3,
            'neural_network' => 0.2,
            'gradient_boosting' => 0.2
        ];
        
        foreach ($predictions as $model => $prediction) {
            $score = $prediction['prediction'] ?? $prediction['anomaly_score'] ?? $prediction['probability'] ?? 0;
            $confidence = $prediction['confidence'] ?? 1.0;
            
            $totalScore += $score * $confidence * $weights[$model];
            $totalWeight += $confidence * $weights[$model];
        }
        
        return $totalWeight > 0 ? ($totalScore / $totalWeight) : 0;
    }
    
    private function ruleBasedDetection(array $transactionData): array
    {
        $factors = [];
        $totalScore = 0;
        
        // Amount-based rules
        if ($transactionData['amount'] > 10000000) {
            $factors[] = ['type' => 'high_amount', 'score' => 0.3, 'reason' => 'High transaction amount'];
            $totalScore += 0.3;
        }
        
        // Time-based rules
        $hour = (int)date('H', $transactionData['timestamp'] ?? time());
        if ($hour < 6 || $hour > 22) {
            $factors[] = ['type' => 'unusual_time', 'score' => 0.2, 'reason' => 'Unusual transaction time'];
            $totalScore += 0.2;
        }
        
        // Location-based rules
        if (isset($transactionData['location_distance']) && $transactionData['location_distance'] > 1000) {
            $factors[] = ['type' => 'far_location', 'score' => 0.4, 'reason' => 'Transaction far from usual location'];
            $totalScore += 0.4;
        }
        
        // Frequency-based rules
        $recentCount = $this->getRecentTransactionCount($transactionData['user_id'] ?? 0, 60); // last hour
        if ($recentCount > 5) {
            $factors[] = ['type' => 'high_frequency', 'score' => 0.3, 'reason' => 'High transaction frequency'];
            $totalScore += 0.3;
        }
        
        return [
            'factors' => $factors,
            'score' => min(1.0, $totalScore)
        ];
    }
    
    private function generateFraudRecommendations(array $detection, array $transactionData): array
    {
        $recommendations = [];
        
        if ($detection['should_block']) {
            $recommendations[] = 'Block transaction immediately';
            $recommendations[] = 'Notify security team';
            $recommendations[] = 'Temporarily suspend user account';
        } elseif ($detection['requires_review']) {
            $recommendations[] = 'Flag for manual review';
            $recommendations[] = 'Request additional verification';
            $recommendations[] = 'Monitor user activity closely';
        } elseif ($detection['risk_score'] > 0.3) {
            $recommendations[] = 'Send security alert to user';
            $recommendations[] = 'Log for future reference';
        }
        
        // Add specific recommendations based on ML predictions
        foreach ($detection['ml_prediction'] as $model => $prediction) {
            if (($prediction['prediction'] ?? 0) > 0.8) {
                $recommendations[] = "High fraud risk detected by {$model}";
            }
        }
        
        return array_unique($recommendations);
    }
    
    // Additional placeholder methods for implementation
    private function logFraudDetection(array $transactionData, array $detection): void {}
    private function sendFraudAlert(array $transactionData, array $detection): void {}
    private function getUserBehaviorBaseline(int $userId): array { return []; }
    private function establishUserBaseline(int $userId, array $transactions): void {}
    private function analyzeAmountPatterns(array $baseline, array $transactions): array { return ['is_anomaly' => false]; }
    private function analyzeFrequencyPatterns(array $baseline, array $transactions): array { return ['is_anomaly' => false]; }
    private function analyzeTimingPatterns(array $baseline, array $transactions): array { return ['is_anomaly' => false]; }
    private function analyzeLocationPatterns(array $baseline, array $transactions): array { return ['is_anomaly' => false]; }
    private function analyzeDevicePatterns(array $baseline, array $transactions): array { return ['is_anomaly' => false]; }
    private function analyzeMerchantPatterns(array $baseline, array $transactions): array { return ['is_anomaly' => false]; }
    private function identifyRiskIndicators(array $behavioralChanges): array { return []; }
    private function extractCurrentPatterns(array $transactions): array { return []; }
    private function generateBehavioralRecommendations(array $analysis): array { return []; }
    private function updateUserBaseline(int $userId, array $transactions): void {}
    private function buildTransactionNetwork(): array { return []; }
    private function detectSuspiciousClusters(array $network): array { return []; }
    private function analyzeCluster(array $cluster): array { return ['risk_score' => 0]; }
    private function findConnectedHighRiskAccounts(array $network): array { return []; }
    private function generateNetworkRecommendations(array $analysis): array { return []; }
    private function calculateBaseRiskScore(array $transactionData): float { return 0.1; }
    private function getUserRiskProfile(int $userId): array { return []; }
    private function getUserHistoryAdjustment(array $profile): array { return ['multiplier' => 1.0]; }
    private function getRecentActivityAdjustment(int $userId): array { return ['multiplier' => 1.0]; }
    private function getSeasonalAdjustment(array $transactionData): array { return ['multiplier' => 1.0]; }
    private function getMarketConditionAdjustment(): array { return ['multiplier' => 1.0]; }
    private function getDeviceTrustAdjustment(array $transactionData): array { return ['multiplier' => 1.0]; }
    private function getLocationTrustAdjustment(array $transactionData): array { return ['multiplier' => 1.0]; }
    private function updateUserRiskProfile(int $userId, array $transactionData, array $riskScoring): void {}
    private function determineRiskLevel(float $score): string { return 'low'; }
    private function getTransactionCount(string $period): int { return 0; }
    private function getBlockedFraudCount(string $period): int { return 0; }
    private function getFraudDetectionRate(string $period): float { return 0.0; }
    private function getFalsePositiveRate(string $period): float { return 0.0; }
    private function getAverageRiskScore(string $period): float { return 0.0; }
    private function getHighRiskTransactionCount(string $period): int { return 0; }
    private function getRealTimeFraudAlerts(): array { return []; }
    private function getTrendingFraudPatterns(): array { return []; }
    private function getMLModelAccuracy(): float { return 0.0; }
    private function getRuleEffectiveness(): float { return 0.0; }
    private function getPreventionSavings(): float { return 0.0; }
    private function getAverageDetectionSpeed(): float { return 0.0; }
    private function getFraudPreventionActionItems(): array { return []; }
    private function calculateLocationDistance(array $transactionData): float { return 0; }
    private function getDeviceTrustScore(array $transactionData): float { return 1.0; }
    private function getMerchantRiskScore(array $transactionData): float { return 0.0; }
    private function getUserRiskScore(int $userId): float { return 0.0; }
    private function getFrequencyScore(array $transactionData): float { return 0.0; }
    private function getRecentTransactionCount(int $userId, int $minutes): int { return 0; }
}

/**
 * Usage Examples:
 * 
 * $fraudService = new FraudPreventionService();
 * 
 * // Real-time fraud detection
 * $detection = $fraudService->detectFraudRealTime([
 *     'user_id' => 123,
 *     'amount' => 5000000,
 *     'timestamp' => time(),
 *     'location_lat' => -6.2088,
 *     'location_lng' => 106.8456,
 *     'device_id' => 'abc123'
 * ]);
 * 
 * // Behavioral pattern analysis
 * $behaviorAnalysis = $fraudService->analyzeBehavioralPatterns(123, $recentTransactions);
 * 
 * // Get fraud prevention dashboard
 * $dashboard = $fraudService->getFraudPreventionDashboard();
 */
