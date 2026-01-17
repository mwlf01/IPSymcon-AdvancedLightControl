<?php
declare(strict_types=1);

class AdvancedLightControl extends IPSModule
{
    private const VM_UPDATE = 10603;

    /* ================= Lifecycle ================= */
    public function Create()
    {
        parent::Create();

        // ---- Properties: Lamps ----
        $this->RegisterPropertyString('Lamps', '[]');

        // ---- Properties: Auto-Off Feature ----
        $this->RegisterPropertyString('LightName', '');
        $this->RegisterPropertyString('LightLocation', '');

        // ---- Properties: Tile Visualizations for Push Notifications ----
        $this->RegisterPropertyString('TileVisualizations', '[]');

        // ---- Properties: Presence Detectors ----
        $this->RegisterPropertyString('PresenceDetectors', '[]');

        // ---- Properties: Brightness Sensor ----
        $this->RegisterPropertyInteger('BrightnessSensor', 0);

        // ---- Properties: Light Switches ----
        $this->RegisterPropertyString('LightSwitches', '[]');
        $this->RegisterPropertyInteger('SwitchMode', 0); // 0=Push-button, 1=Toggle on change, 2=On-only

        // ---- Profiles ----
        $this->ensureProfiles();

        // ---- Timers ----
        $this->RegisterTimer('AutoOff', 0, 'ALC_AutoOff($_IPS[\'TARGET\']);');
        $this->RegisterTimer('CountdownTick', 0, 'ALC_CountdownTick($_IPS[\'TARGET\']);');
        $this->RegisterTimer('PresenceFollowUp', 0, 'ALC_PresenceFollowUp($_IPS[\'TARGET\']);');

        // ---- Attributes ----
        $this->RegisterAttributeInteger('AutoOffUntil', 0);
        $this->RegisterAttributeBoolean('NotificationSent', false);
        $this->RegisterAttributeBoolean('AutoOffTriggered', false);
        $this->RegisterAttributeBoolean('ManualSwitchOn', false);
        $this->RegisterAttributeBoolean('PresenceWasActiveOnManualSwitch', false);
        $this->RegisterAttributeBoolean('PushButtonState', false); // For push-button mode: false=next push turns on, true=next push turns off
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->ensureProfiles();

        // Always create master switch variable
        $this->MaintainVariable('MasterSwitch', $this->Translate('All Lights'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Light',
            'ICON_OFF' => 'Light'
        ], 1, true);
        $this->EnableAction('MasterSwitch');

        // Light switches variables (position 2)
        $this->MaintainVariable('SwitchesEnabled', $this->Translate('Light Switches'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Power',
            'ICON_OFF' => 'Power'
        ], 2, true);
        $this->EnableAction('SwitchesEnabled');
        $this->initializeVariableDefault('SwitchesEnabled', false);

        // Presence detection variables (positions 3-4)
        $this->MaintainVariable('PresenceEnabled', $this->Translate('Presence Detection'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Motion',
            'ICON_OFF' => 'Motion'
        ], 3, true);
        $this->MaintainVariable('PresenceFollowUpTime', $this->Translate('Presence Follow-Up Time'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' s'
        ], 4, true);
        $this->EnableAction('PresenceEnabled');
        $this->EnableAction('PresenceFollowUpTime');
        $this->initializeVariableDefault('PresenceEnabled', false);
        $this->initializeVariableDefault('PresenceFollowUpTime', 60);

        // Brightness control variables (positions 5-6)
        $this->MaintainVariable('BrightnessEnabled', $this->Translate('Brightness Control'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Sun',
            'ICON_OFF' => 'Sun'
        ], 5, true);
        $this->MaintainVariable('BrightnessThreshold', $this->Translate('Brightness Threshold'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' lux'
        ], 6, true);
        $this->EnableAction('BrightnessEnabled');
        $this->EnableAction('BrightnessThreshold');
        $this->initializeVariableDefault('BrightnessEnabled', false);
        $this->initializeVariableDefault('BrightnessThreshold', 100);

        // Auto-off variables (positions 7-12)
        $this->MaintainVariable('AutoOffEnabled', $this->Translate('Auto-Off'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Clock',
            'ICON_OFF' => 'Clock'
        ], 7, true);
        $this->MaintainVariable('AutoOffTime', $this->Translate('Auto-Off Time'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' s'
        ], 8, true);
        $this->MaintainVariable('RemainingTime', $this->Translate('Remaining Time'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_DURATION,
            'DISPLAY_TYPE' => 'Value',
            'DISPLAY_UNIT' => 'Seconds',
            'FORMAT' => 2
        ], 9, true);
        $this->MaintainVariable('ExtendTimer', $this->Translate('Extend Timer'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
            'OPTIONS' => json_encode([
                ['Value' => 0, 'Caption' => $this->Translate('Extend Timer'), 'IconActive' => true, 'IconValue' => 'Clock', 'Color' => 0x00FF00]
            ])
        ], 10, true);
        $this->MaintainVariable('NotificationsEnabled', $this->Translate('Notifications'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Speaker',
            'ICON_OFF' => 'Speaker'
        ], 11, true);
        $this->MaintainVariable('NotificationThreshold', $this->Translate('Notify Before'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' s'
        ], 12, true);
        $this->EnableAction('AutoOffEnabled');
        $this->EnableAction('AutoOffTime');
        $this->EnableAction('ExtendTimer');
        $this->EnableAction('NotificationsEnabled');
        $this->EnableAction('NotificationThreshold');
        $this->initializeVariableDefault('AutoOffEnabled', false);
        $this->initializeVariableDefault('AutoOffTime', 300);
        $this->initializeVariableDefault('NotificationsEnabled', false);
        $this->initializeVariableDefault('NotificationThreshold', 60);

        // Register message subscriptions for lamps, presence detectors, and brightness sensor
        $this->registerMessages();

        // Stop timers on config change
        $this->SetTimerInterval('AutoOff', 0);
        $this->SetTimerInterval('CountdownTick', 0);
        $this->SetTimerInterval('PresenceFollowUp', 0);
        $this->WriteAttributeInteger('AutoOffUntil', 0);
        $this->WriteAttributeBoolean('NotificationSent', false);
        $this->WriteAttributeBoolean('AutoOffTriggered', false);
        $this->WriteAttributeBoolean('ManualSwitchOn', false);
        $this->WriteAttributeBoolean('PresenceWasActiveOnManualSwitch', false);
        $this->WriteAttributeBoolean('PushButtonState', false);

        @SetValue($this->GetIDForIdent('RemainingTime'), 0);

        // Update master switch state based on current lamp states
        $this->updateMasterSwitchState();
    }

    /* ================= Configuration Form ================= */
    public function GetConfigurationForm(): string
    {
        return json_encode([
            'elements' => [
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Lamps',
                    'expanded' => true,
                    'items' => [
                        [
                            'type' => 'List',
                            'name' => 'Lamps',
                            'caption' => 'Boolean Variables (Lamps)',
                            'rowCount' => 5,
                            'add' => true,
                            'delete' => true,
                            'columns' => [
                                [
                                    'caption' => 'Lamp Variable',
                                    'name' => 'LampID',
                                    'width' => '400px',
                                    'add' => 0,
                                    'edit' => [
                                        'type' => 'SelectVariable',
                                        'validVariableTypes' => [VARIABLETYPE_BOOLEAN]
                                    ]
                                ],
                                [
                                    'caption' => 'Name',
                                    'name' => 'Name',
                                    'width' => 'auto',
                                    'add' => '',
                                    'edit' => [
                                        'type' => 'ValidationTextBox'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Light Switches',
                    'items' => [
                        [
                            'type' => 'Select',
                            'name' => 'SwitchMode',
                            'caption' => 'Switch Mode',
                            'options' => [
                                ['caption' => 'Push-button (toggle on press)', 'value' => 0],
                                ['caption' => 'Toggle on any change', 'value' => 1],
                                ['caption' => 'On-only (staircase lighting)', 'value' => 2]
                            ]
                        ],
                        [
                            'type' => 'List',
                            'name' => 'LightSwitches',
                            'caption' => 'Light Switches',
                            'rowCount' => 5,
                            'add' => true,
                            'delete' => true,
                            'columns' => [
                                [
                                    'caption' => 'Switch Variable',
                                    'name' => 'SwitchID',
                                    'width' => '400px',
                                    'add' => 0,
                                    'edit' => [
                                        'type' => 'SelectVariable',
                                        'validVariableTypes' => [VARIABLETYPE_BOOLEAN]
                                    ]
                                ],
                                [
                                    'caption' => 'Name',
                                    'name' => 'Name',
                                    'width' => 'auto',
                                    'add' => '',
                                    'edit' => [
                                        'type' => 'ValidationTextBox'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Presence Detection',
                    'items' => [
                        [
                            'type' => 'List',
                            'name' => 'PresenceDetectors',
                            'caption' => 'Presence Detectors',
                            'rowCount' => 5,
                            'add' => true,
                            'delete' => true,
                            'columns' => [
                                [
                                    'caption' => 'Presence Detector Variable',
                                    'name' => 'DetectorID',
                                    'width' => '400px',
                                    'add' => 0,
                                    'edit' => [
                                        'type' => 'SelectVariable',
                                        'validVariableTypes' => [VARIABLETYPE_BOOLEAN]
                                    ]
                                ],
                                [
                                    'caption' => 'Name',
                                    'name' => 'Name',
                                    'width' => 'auto',
                                    'add' => '',
                                    'edit' => [
                                        'type' => 'ValidationTextBox'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Brightness Control',
                    'items' => [
                        [
                            'type' => 'SelectVariable',
                            'name' => 'BrightnessSensor',
                            'caption' => 'Brightness Sensor Variable',
                            'validVariableTypes' => [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT]
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Notifications',
                    'items' => [
                        [
                            'type' => 'ValidationTextBox',
                            'name' => 'LightName',
                            'caption' => 'Light Name'
                        ],
                        [
                            'type' => 'ValidationTextBox',
                            'name' => 'LightLocation',
                            'caption' => 'Light Location'
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Tile Visualizations',
                    'items' => [
                        [
                            'type' => 'List',
                            'name' => 'TileVisualizations',
                            'caption' => 'Tile Visualization Instances',
                            'rowCount' => 3,
                            'add' => true,
                            'delete' => true,
                            'columns' => [
                                [
                                    'caption' => 'Tile Visualization',
                                    'name' => 'VisuID',
                                    'width' => '400px',
                                    'add' => 0,
                                    'edit' => [
                                        'type' => 'SelectInstance'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'actions' => [],
            'status' => [
                [
                    'code' => 102,
                    'icon' => 'active',
                    'caption' => 'Module is active'
                ],
                [
                    'code' => 104,
                    'icon' => 'inactive',
                    'caption' => 'No lamps configured'
                ]
            ]
        ]);
    }

    /* ================= Action Handling ================= */
    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'MasterSwitch':
                $this->SwitchAll((bool)$Value);
                break;

            case 'AutoOffEnabled':
                SetValue($this->GetIDForIdent('AutoOffEnabled'), (bool)$Value);
                if (!(bool)$Value) {
                    $this->stopTimer();
                }
                break;

            case 'AutoOffTime':
                $val = max(1, min(172800, (int)$Value));
                SetValue($this->GetIDForIdent('AutoOffTime'), $val);
                break;

            case 'ExtendTimer':
                $this->ExtendTimer();
                SetValue($this->GetIDForIdent('ExtendTimer'), 0);
                break;

            case 'NotificationsEnabled':
                SetValue($this->GetIDForIdent('NotificationsEnabled'), (bool)$Value);
                break;

            case 'NotificationThreshold':
                $val = max(1, min(172800, (int)$Value));
                SetValue($this->GetIDForIdent('NotificationThreshold'), $val);
                break;

            case 'PresenceEnabled':
                SetValue($this->GetIDForIdent('PresenceEnabled'), (bool)$Value);
                break;

            case 'PresenceFollowUpTime':
                $val = max(1, min(172800, (int)$Value));
                SetValue($this->GetIDForIdent('PresenceFollowUpTime'), $val);
                break;

            case 'BrightnessEnabled':
                SetValue($this->GetIDForIdent('BrightnessEnabled'), (bool)$Value);
                break;

            case 'BrightnessThreshold':
                $val = max(0, (int)$Value);
                SetValue($this->GetIDForIdent('BrightnessThreshold'), $val);
                break;

            case 'SwitchesEnabled':
                SetValue($this->GetIDForIdent('SwitchesEnabled'), (bool)$Value);
                break;

            default:
                throw new Exception('Invalid Ident: ' . $Ident);
        }
    }

    /* ================= Message Sink ================= */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message !== self::VM_UPDATE) {
            return;
        }

        // Check if sender is one of our lamps
        $lamps = $this->getLamps();
        $isLamp = false;
        foreach ($lamps as $lamp) {
            if ((int)$lamp['LampID'] === $SenderID) {
                $isLamp = true;
                break;
            }
        }

        if ($isLamp) {
            $this->updateMasterSwitchState();
            return;
        }

        // Check if sender is a presence detector
        $detectors = $this->getPresenceDetectors();
        foreach ($detectors as $detector) {
            if ((int)$detector['DetectorID'] === $SenderID) {
                $this->handlePresenceChange();
                return;
            }
        }

        // Check if sender is the brightness sensor
        $brightnessSensorID = $this->ReadPropertyInteger('BrightnessSensor');
        if ($SenderID === $brightnessSensorID) {
            $this->handleBrightnessChange();
            return;
        }

        // Check if sender is a light switch
        $switches = $this->getLightSwitches();
        foreach ($switches as $switch) {
            if ((int)$switch['SwitchID'] === $SenderID) {
                $this->handleSwitchChange($SenderID, $Data);
                return;
            }
        }
    }

    /* ================= Public Functions ================= */

    /**
     * Switch all registered lamps on or off
     * @param bool $State - true to turn on, false to turn off
     * @param bool $ByPresence - true if triggered by presence detection
     * @param bool $ByAutoOff - true if triggered by auto-off timer
     */
    public function SwitchAll(bool $State, bool $ByPresence = false, bool $ByAutoOff = false): void
    {
        $lamps = $this->getLamps();

        foreach ($lamps as $lamp) {
            $lampID = (int)$lamp['LampID'];
            if ($lampID > 0 && @IPS_VariableExists($lampID)) {
                $current = (bool)@GetValue($lampID);
                if ($current !== $State) {
                    @RequestAction($lampID, $State);
                }
            }
        }

        SetValue($this->GetIDForIdent('MasterSwitch'), $State);

        if ($State) {
            // Turning on
            if ($ByPresence) {
                // Presence-triggered switch-on
                $this->WriteAttributeBoolean('ManualSwitchOn', false);
                $this->WriteAttributeBoolean('PresenceWasActiveOnManualSwitch', false);
            } else {
                // Manual switch-on (via MasterSwitch or API)
                $this->WriteAttributeBoolean('ManualSwitchOn', true);
                // Track if presence was already active when manually switched on
                $this->WriteAttributeBoolean('PresenceWasActiveOnManualSwitch', $this->isAnyPresenceDetected());
            }
            $this->WriteAttributeBoolean('AutoOffTriggered', false);
            $this->SetTimerInterval('PresenceFollowUp', 0);

            // Handle auto-off timer
            if ($this->isAutoOffActive()) {
                $this->armAutoOffTimer();
            }
        } else {
            // Turning off
            if ($ByAutoOff) {
                $this->WriteAttributeBoolean('AutoOffTriggered', true);
            }
            $this->WriteAttributeBoolean('ManualSwitchOn', false);
            $this->WriteAttributeBoolean('PresenceWasActiveOnManualSwitch', false);
            $this->WriteAttributeBoolean('PushButtonState', false); // Reset push-button state when lights turn off
            $this->stopTimer();
            $this->SetTimerInterval('PresenceFollowUp', 0);
        }
    }

    /**
     * Extend/Reset the auto-off timer
     */
    public function ExtendTimer(): void
    {
        $masterSwitch = @GetValue($this->GetIDForIdent('MasterSwitch'));
        if ($masterSwitch && $this->isAutoOffActive()) {
            $this->armAutoOffTimer();
            $this->WriteAttributeBoolean('NotificationSent', false);
        }
    }

    /**
     * Get the current remaining time in seconds
     */
    public function GetRemainingTime(): int
    {
        $until = $this->ReadAttributeInteger('AutoOffUntil');
        return max(0, $until - time());
    }

    /**
     * Timer callback: Auto-Off
     */
    public function AutoOff(): void
    {
        $masterSwitch = @GetValue($this->GetIDForIdent('MasterSwitch'));
        if ($masterSwitch) {
            $this->SwitchAll(false, false, true);
        }
        $this->stopTimer();
    }

    /**
     * Timer callback: Presence Follow-Up (switch off after presence ended)
     */
    public function PresenceFollowUp(): void
    {
        $this->SetTimerInterval('PresenceFollowUp', 0);

        // Only switch off if lights are still on and no presence is detected
        $masterSwitch = @GetValue($this->GetIDForIdent('MasterSwitch'));
        if ($masterSwitch && !$this->isAnyPresenceDetected()) {
            $this->SwitchAll(false, true, false);
        }
    }

    /**
     * Timer callback: Countdown tick
     */
    public function CountdownTick(): void
    {
        $until = $this->ReadAttributeInteger('AutoOffUntil');
        if ($until <= 0) {
            $this->SetTimerInterval('CountdownTick', 0);
            @SetValue($this->GetIDForIdent('RemainingTime'), 0);
            return;
        }

        $remaining = max(0, $until - time());
        @SetValue($this->GetIDForIdent('RemainingTime'), $remaining);

        // Check notification threshold
        if ($this->areNotificationsEnabled() && !$this->ReadAttributeBoolean('NotificationSent')) {
            $threshold = $this->getNotificationThreshold();
            if ($remaining > 0 && $remaining <= $threshold) {
                $this->sendNotifications($remaining);
                $this->WriteAttributeBoolean('NotificationSent', true);
            }
        }

        if ($remaining === 0) {
            $this->SetTimerInterval('CountdownTick', 0);
        }
    }

    /**
     * Set the auto-off time
     */
    public function SetAutoOffTime(int $Seconds): void
    {
        $seconds = max(1, min(86400, $Seconds));
        SetValue($this->GetIDForIdent('AutoOffTime'), $seconds);
    }

    /**
     * Enable or disable auto-off
     */
    public function SetAutoOffEnabled(bool $Enabled): void
    {
        SetValue($this->GetIDForIdent('AutoOffEnabled'), $Enabled);
        if (!$Enabled) {
            $this->stopTimer();
        }
    }

    /* ================= Private Helper Functions ================= */

    private function getLamps(): array
    {
        $raw = @json_decode($this->ReadPropertyString('Lamps'), true);
        if (!is_array($raw)) {
            return [];
        }
        return array_filter($raw, function ($lamp) {
            $id = (int)($lamp['LampID'] ?? 0);
            return $id > 0 && @IPS_VariableExists($id);
        });
    }

    private function getTileVisualizations(): array
    {
        $raw = @json_decode($this->ReadPropertyString('TileVisualizations'), true);
        if (!is_array($raw)) {
            return [];
        }
        return array_filter($raw, function ($visu) {
            $id = (int)($visu['VisuID'] ?? 0);
            return $id > 0 && @IPS_InstanceExists($id);
        });
    }

    private function registerMessages(): void
    {
        // Unregister all previous
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        // Register lamp variables
        $lamps = $this->getLamps();
        foreach ($lamps as $lamp) {
            $lampID = (int)$lamp['LampID'];
            if ($lampID > 0) {
                $this->RegisterMessage($lampID, self::VM_UPDATE);
            }
        }

        // Register presence detector variables
        $detectors = $this->getPresenceDetectors();
        foreach ($detectors as $detector) {
            $detectorID = (int)$detector['DetectorID'];
            if ($detectorID > 0) {
                $this->RegisterMessage($detectorID, self::VM_UPDATE);
            }
        }

        // Register brightness sensor variable
        $brightnessSensorID = $this->ReadPropertyInteger('BrightnessSensor');
        if ($brightnessSensorID > 0 && @IPS_VariableExists($brightnessSensorID)) {
            $this->RegisterMessage($brightnessSensorID, self::VM_UPDATE);
        }

        // Register light switch variables
        $switches = $this->getLightSwitches();
        foreach ($switches as $switch) {
            $switchID = (int)$switch['SwitchID'];
            if ($switchID > 0) {
                $this->RegisterMessage($switchID, self::VM_UPDATE);
            }
        }
    }

    private function getLightSwitches(): array
    {
        $raw = @json_decode($this->ReadPropertyString('LightSwitches'), true);
        if (!is_array($raw)) {
            return [];
        }
        return array_filter($raw, function ($switch) {
            $id = (int)($switch['SwitchID'] ?? 0);
            return $id > 0 && @IPS_VariableExists($id);
        });
    }

    private function getPresenceDetectors(): array
    {
        $raw = @json_decode($this->ReadPropertyString('PresenceDetectors'), true);
        if (!is_array($raw)) {
            return [];
        }
        return array_filter($raw, function ($detector) {
            $id = (int)($detector['DetectorID'] ?? 0);
            return $id > 0 && @IPS_VariableExists($id);
        });
    }

    private function isAnyPresenceDetected(): bool
    {
        $detectors = $this->getPresenceDetectors();
        foreach ($detectors as $detector) {
            $detectorID = (int)$detector['DetectorID'];
            if ($detectorID > 0 && @IPS_VariableExists($detectorID)) {
                if ((bool)@GetValue($detectorID)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isPresenceDetectionEnabled(): bool
    {
        $presenceEnabledID = @$this->GetIDForIdent('PresenceEnabled');
        if ($presenceEnabledID && @IPS_VariableExists($presenceEnabledID)) {
            return (bool)@GetValue($presenceEnabledID);
        }
        return false;
    }

    private function isBrightnessBelowThreshold(): bool
    {
        // Check if brightness control is enabled via variable
        $brightnessEnabledID = @$this->GetIDForIdent('BrightnessEnabled');
        if (!$brightnessEnabledID || !@IPS_VariableExists($brightnessEnabledID)) {
            return true; // Variable doesn't exist, allow lights
        }
        if (!(bool)@GetValue($brightnessEnabledID)) {
            return true; // Brightness control disabled by user, always allow
        }

        $brightnessSensorID = $this->ReadPropertyInteger('BrightnessSensor');
        if ($brightnessSensorID <= 0 || !@IPS_VariableExists($brightnessSensorID)) {
            return true; // No sensor configured, allow
        }

        $currentBrightness = @GetValue($brightnessSensorID);
        $threshold = $this->getBrightnessThreshold();

        return $currentBrightness < $threshold;
    }

    private function getBrightnessThreshold(): float
    {
        $brightnessThresholdID = @$this->GetIDForIdent('BrightnessThreshold');
        if ($brightnessThresholdID && @IPS_VariableExists($brightnessThresholdID)) {
            $val = @GetValue($brightnessThresholdID);
            if ($val >= 0) {
                return (float)$val;
            }
        }
        return 100.0; // Default: 100 lux
    }

    private function getPresenceFollowUpTime(): int
    {
        $followUpTimeID = @$this->GetIDForIdent('PresenceFollowUpTime');
        if ($followUpTimeID && @IPS_VariableExists($followUpTimeID)) {
            $val = (int)@GetValue($followUpTimeID);
            if ($val > 0) {
                return $val;
            }
        }
        return 60; // Default: 60 seconds
    }

    private function handlePresenceChange(): void
    {
        if (!$this->isPresenceDetectionEnabled()) {
            return;
        }

        $masterSwitch = @GetValue($this->GetIDForIdent('MasterSwitch'));
        $anyPresence = $this->isAnyPresenceDetected();

        if ($anyPresence) {
            // Presence detected - cancel any follow-up timer
            $this->SetTimerInterval('PresenceFollowUp', 0);

            // Check if we should turn on lights
            if (!$masterSwitch) {
                // Lights are off - check if we should turn them on

                // Don't turn on if auto-off was triggered and presence was already active
                if ($this->ReadAttributeBoolean('AutoOffTriggered')) {
                    // Auto-off was triggered, don't turn on by presence until presence goes false first
                    return;
                }

                // Check brightness threshold
                if ($this->isBrightnessBelowThreshold()) {
                    $this->SwitchAll(true, true, false);
                }
            }
        } else {
            // No presence detected
            // Reset auto-off triggered flag so presence can work again after presence goes false
            $this->WriteAttributeBoolean('AutoOffTriggered', false);

            if ($masterSwitch) {
                // Lights are on - check if we should start follow-up timer
                // Only if lights were turned on by presence (not manual)
                if (!$this->ReadAttributeBoolean('ManualSwitchOn')) {
                    // Start presence follow-up timer
                    $followUpTime = $this->getPresenceFollowUpTime();
                    $this->SetTimerInterval('PresenceFollowUp', $followUpTime * 1000);
                }
            }
        }
    }

    private function handleBrightnessChange(): void
    {
        // Brightness change can trigger presence-based turn-on if presence is already detected
        if (!$this->isPresenceDetectionEnabled()) {
            return;
        }

        $masterSwitch = @GetValue($this->GetIDForIdent('MasterSwitch'));

        // Only relevant when lights are off
        if ($masterSwitch) {
            return;
        }

        // Check if presence is detected and brightness is now below threshold
        if ($this->isAnyPresenceDetected() && $this->isBrightnessBelowThreshold()) {
            // Don't turn on if auto-off was triggered
            if (!$this->ReadAttributeBoolean('AutoOffTriggered')) {
                $this->SwitchAll(true, true, false);
            }
        }
    }

    private function handleSwitchChange(int $senderID, array $data): void
    {
        // Check if switches are enabled via variable
        $switchesEnabledID = @$this->GetIDForIdent('SwitchesEnabled');
        if (!$switchesEnabledID || !@IPS_VariableExists($switchesEnabledID)) {
            return; // Variable doesn't exist, switches disabled
        }
        if (!(bool)@GetValue($switchesEnabledID)) {
            return; // Switches disabled by user
        }

        // Get switch mode: 0=Push-button, 1=Toggle on change, 2=On-only
        $switchMode = $this->ReadPropertyInteger('SwitchMode');
        $masterSwitch = @GetValue($this->GetIDForIdent('MasterSwitch'));

        // $data[0] contains the new value of the variable
        $newValue = isset($data[0]) ? (bool)$data[0] : false;

        switch ($switchMode) {
            case 0: // Push-button mode
                // Only react to true signals (button press)
                if ($newValue) {
                    $pushState = $this->ReadAttributeBoolean('PushButtonState');
                    if (!$pushState) {
                        // Next push turns on
                        $this->SwitchAll(true);
                        $this->WriteAttributeBoolean('PushButtonState', true);
                    } else {
                        // Next push turns off
                        $this->SwitchAll(false);
                        $this->WriteAttributeBoolean('PushButtonState', false);
                    }
                }
                break;

            case 1: // Toggle on any change
                // Any change (true->false or false->true) toggles the lights
                $this->SwitchAll(!$masterSwitch);
                break;

            case 2: // On-only (staircase lighting)
                // Only react to true signals, always turn on
                if ($newValue) {
                    $this->SwitchAll(true);
                }
                break;
        }
    }

    private function updateMasterSwitchState(): void
    {
        $lamps = $this->getLamps();
        if (empty($lamps)) {
            $this->SetStatus(104);
            return;
        }

        $this->SetStatus(102);

        $anyOn = false;
        foreach ($lamps as $lamp) {
            $lampID = (int)$lamp['LampID'];
            if ($lampID > 0 && @IPS_VariableExists($lampID)) {
                if ((bool)@GetValue($lampID)) {
                    $anyOn = true;
                    break;
                }
            }
        }

        $masterSwitchID = @$this->GetIDForIdent('MasterSwitch');
        if ($masterSwitchID) {
            $current = (bool)@GetValue($masterSwitchID);
            if ($current !== $anyOn) {
                SetValue($masterSwitchID, $anyOn);
            }
        }
    }

    private function isAutoOffActive(): bool
    {
        $autoOffEnabledID = @$this->GetIDForIdent('AutoOffEnabled');
        if (!$autoOffEnabledID) {
            return false;
        }

        return (bool)@GetValue($autoOffEnabledID);
    }

    private function getAutoOffTime(): int
    {
        $autoOffTimeID = @$this->GetIDForIdent('AutoOffTime');
        if ($autoOffTimeID && @IPS_VariableExists($autoOffTimeID)) {
            $val = (int)@GetValue($autoOffTimeID);
            if ($val > 0) {
                return $val;
            }
        }
        return 300; // Default: 5 minutes
    }

    private function areNotificationsEnabled(): bool
    {
        $notificationsEnabledID = @$this->GetIDForIdent('NotificationsEnabled');
        if ($notificationsEnabledID && @IPS_VariableExists($notificationsEnabledID)) {
            return (bool)@GetValue($notificationsEnabledID);
        }
        return false;
    }

    private function getNotificationThreshold(): int
    {
        $notificationThresholdID = @$this->GetIDForIdent('NotificationThreshold');
        if ($notificationThresholdID && @IPS_VariableExists($notificationThresholdID)) {
            $val = (int)@GetValue($notificationThresholdID);
            if ($val > 0) {
                return $val;
            }
        }
        return 60; // Default: 60 seconds
    }

    private function armAutoOffTimer(): void
    {
        $timeout = $this->getAutoOffTime();
        $until = time() + $timeout;

        $this->WriteAttributeInteger('AutoOffUntil', $until);
        $this->WriteAttributeBoolean('NotificationSent', false);

        $this->SetTimerInterval('AutoOff', $timeout * 1000);
        $this->SetTimerInterval('CountdownTick', 1000);

        @SetValue($this->GetIDForIdent('RemainingTime'), $timeout);
    }

    private function stopTimer(): void
    {
        $this->SetTimerInterval('AutoOff', 0);
        $this->SetTimerInterval('CountdownTick', 0);
        $this->WriteAttributeInteger('AutoOffUntil', 0);
        $this->WriteAttributeBoolean('NotificationSent', false);

        $remainingID = @$this->GetIDForIdent('RemainingTime');
        if ($remainingID) {
            @SetValue($remainingID, 0);
        }
    }

    /**
     * Initialize a variable with a default value only if it hasn't been set yet
     */
    private function initializeVariableDefault(string $ident, $defaultValue): void
    {
        $varID = @$this->GetIDForIdent($ident);
        if (!$varID || !@IPS_VariableExists($varID)) {
            return;
        }

        $variable = IPS_GetVariable($varID);
        
        // Check if this is a freshly created variable by looking at LastChange
        // If LastChange equals creation time (within 1 second), initialize with default
        $lastChange = $variable['VariableChanged'];
        $created = $variable['VariableUpdated'];
        
        // If the variable has never been explicitly set (LastChange == 0 or equals creation)
        if ($lastChange == 0 || abs($lastChange - $created) < 2) {
            SetValue($varID, $defaultValue);
        }
    }

    private function sendNotifications(int $remainingSeconds): void
    {
        $visualizations = $this->getTileVisualizations();
        if (empty($visualizations)) {
            return;
        }

        // Build title from light name and location
        $lightName = $this->ReadPropertyString('LightName');
        $lightLocation = $this->ReadPropertyString('LightLocation');

        if (!empty($lightName) && !empty($lightLocation)) {
            $title = $lightName . ' (' . $lightLocation . ')';
        } elseif (!empty($lightName)) {
            $title = $lightName;
        } elseif (!empty($lightLocation)) {
            $title = $lightLocation;
        } else {
            $title = $this->Translate('Auto-Off');
        }

        // Fixed message format (localized)
        $message = sprintf($this->Translate('Turns off in %d seconds. Tap to extend.'), $remainingSeconds);

        // Use ExtendTimer variable as target so tapping extends the timer
        $extendTimerID = @$this->GetIDForIdent('ExtendTimer');
        $targetID = $extendTimerID ?: $this->InstanceID;

        foreach ($visualizations as $visu) {
            $visuID = (int)$visu['VisuID'];
            if ($visuID > 0 && @IPS_InstanceExists($visuID)) {
                @VISU_PostNotificationEx($visuID, $title, $message, 'Clock', 'alarm', $targetID);
            }
        }
    }

    private function ensureProfiles(): void
    {
        // Clean up all old profiles - now using new presentation system exclusively
        $oldProfiles = ['ALC.Extend', 'ALC.Seconds', 'ALC.AutoOffTime', 'ALC.NotificationThreshold'];
        foreach ($oldProfiles as $profile) {
            if (IPS_VariableProfileExists($profile)) {
                IPS_DeleteVariableProfile($profile);
            }
        }
    }
}
