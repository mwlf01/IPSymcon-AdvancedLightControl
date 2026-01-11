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
        $this->RegisterPropertyBoolean('EnableAutoOff', false);
        $this->RegisterPropertyInteger('AutoOffTime', 300);
        $this->RegisterPropertyInteger('NotificationThreshold', 60);
        $this->RegisterPropertyBoolean('EnableNotifications', false);
        $this->RegisterPropertyString('LightName', '');
        $this->RegisterPropertyString('LightLocation', '');

        // ---- Properties: Tile Visualizations for Push Notifications ----
        $this->RegisterPropertyString('TileVisualizations', '[]');

        // ---- Properties: Presence Detectors ----
        $this->RegisterPropertyString('PresenceDetectors', '[]');
        $this->RegisterPropertyBoolean('EnablePresenceDetection', false);
        $this->RegisterPropertyInteger('PresenceFollowUpTime', 60);

        // ---- Properties: Brightness Sensor ----
        $this->RegisterPropertyInteger('BrightnessSensor', 0);
        $this->RegisterPropertyBoolean('EnableBrightnessControl', false);
        $this->RegisterPropertyInteger('BrightnessThreshold', 100);

        // ---- Properties: Light Switches ----
        $this->RegisterPropertyString('LightSwitches', '[]');
        $this->RegisterPropertyBoolean('EnableLightSwitches', false);
        $this->RegisterPropertyInteger('SwitchMode', 0); // 0=Push-button, 1=Toggle on change, 2=On-only

        // ---- Properties: Visibility in Visualization ----
        $this->RegisterPropertyBoolean('ShowMasterSwitch', true);
        $this->RegisterPropertyBoolean('ShowAutoOffToggle', true);
        $this->RegisterPropertyBoolean('ShowAutoOffTime', true);
        $this->RegisterPropertyBoolean('ShowRemainingTime', true);
        $this->RegisterPropertyBoolean('ShowExtendButton', true);
        $this->RegisterPropertyBoolean('ShowNotificationToggle', true);
        $this->RegisterPropertyBoolean('ShowNotificationThreshold', true);
        $this->RegisterPropertyBoolean('ShowPresenceToggle', true);
        $this->RegisterPropertyBoolean('ShowPresenceFollowUpTime', true);
        $this->RegisterPropertyBoolean('ShowBrightnessToggle', true);
        $this->RegisterPropertyBoolean('ShowBrightnessThreshold', true);
        $this->RegisterPropertyBoolean('ShowSwitchesToggle', true);

        // ---- Properties: Allow User Configuration in Visualization ----
        $this->RegisterPropertyBoolean('AllowUserAutoOffToggle', true);
        $this->RegisterPropertyBoolean('AllowUserAutoOffTime', true);
        $this->RegisterPropertyBoolean('AllowUserExtend', true);
        $this->RegisterPropertyBoolean('AllowUserNotificationToggle', true);
        $this->RegisterPropertyBoolean('AllowUserNotificationThreshold', true);
        $this->RegisterPropertyBoolean('AllowUserPresenceToggle', true);
        $this->RegisterPropertyBoolean('AllowUserBrightnessToggle', true);
        $this->RegisterPropertyBoolean('AllowUserBrightnessThreshold', true);
        $this->RegisterPropertyBoolean('AllowUserSwitchesToggle', true);

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
        $enableSwitches = $this->ReadPropertyBoolean('EnableLightSwitches');
        $this->MaintainVariable('SwitchesEnabled', $this->Translate('Light Switches Enabled'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Power',
            'ICON_OFF' => 'Power'
        ], 2, $enableSwitches);

        // Presence detection variables (positions 3-4)
        $enablePresence = $this->ReadPropertyBoolean('EnablePresenceDetection');
        $this->MaintainVariable('PresenceEnabled', $this->Translate('Presence Detection Enabled'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Motion',
            'ICON_OFF' => 'Motion'
        ], 3, $enablePresence);
        $this->MaintainVariable('PresenceFollowUpTime', $this->Translate('Presence Follow-Up Time'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' s'
        ], 4, $enablePresence);

        // Brightness control variables (positions 5-6)
        $enableBrightness = $this->ReadPropertyBoolean('EnableBrightnessControl');
        $this->MaintainVariable('BrightnessEnabled', $this->Translate('Brightness Control Enabled'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Sun',
            'ICON_OFF' => 'Sun'
        ], 5, $enableBrightness);
        $this->MaintainVariable('BrightnessThreshold', $this->Translate('Brightness Threshold'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' lux'
        ], 6, $enableBrightness);

        // Auto-off variables (positions 7-12)
        $enableAutoOff = $this->ReadPropertyBoolean('EnableAutoOff');
        $this->MaintainVariable('AutoOffEnabled', $this->Translate('Auto-Off Enabled'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Clock',
            'ICON_OFF' => 'Clock'
        ], 7, $enableAutoOff);
        $this->MaintainVariable('AutoOffTime', $this->Translate('Auto-Off Time'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' s'
        ], 8, $enableAutoOff);
        $this->MaintainVariable('RemainingTime', $this->Translate('Remaining Time'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_DURATION,
            'DISPLAY_TYPE' => 'Value',
            'DISPLAY_UNIT' => 'Seconds',
            'FORMAT' => 2
        ], 9, $enableAutoOff);
        $this->MaintainVariable('ExtendTimer', $this->Translate('Extend Timer'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
            'OPTIONS' => json_encode([
                ['Value' => 0, 'Caption' => $this->Translate('Extend Timer'), 'IconActive' => true, 'IconValue' => 'Clock', 'Color' => 0x00FF00]
            ])
        ], 10, $enableAutoOff);
        $this->MaintainVariable('NotificationsEnabled', $this->Translate('Notifications Enabled'), VARIABLETYPE_BOOLEAN, [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'ICON_ON' => 'Speaker',
            'ICON_OFF' => 'Speaker'
        ], 11, $enableAutoOff);
        $this->MaintainVariable('NotificationThreshold', $this->Translate('Notification Threshold'), VARIABLETYPE_INTEGER, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT,
            'SUFFIX' => ' s'
        ], 12, $enableAutoOff);

        if ($enableSwitches) {
            $this->EnableAction('SwitchesEnabled');
            $switchesEnabledID = @$this->GetIDForIdent('SwitchesEnabled');
            if ($switchesEnabledID) {
                SetValue($switchesEnabledID, true);
            }
        }

        if ($enablePresence) {
            $this->EnableAction('PresenceEnabled');
            $this->EnableAction('PresenceFollowUpTime');
            $presenceEnabledID = @$this->GetIDForIdent('PresenceEnabled');
            if ($presenceEnabledID) {
                SetValue($presenceEnabledID, true);
            }
            $followUpTimeID = @$this->GetIDForIdent('PresenceFollowUpTime');
            if ($followUpTimeID) {
                SetValue($followUpTimeID, $this->ReadPropertyInteger('PresenceFollowUpTime'));
            }
        }

        if ($enableBrightness) {
            $this->EnableAction('BrightnessEnabled');
            $this->EnableAction('BrightnessThreshold');
            $brightnessEnabledID = @$this->GetIDForIdent('BrightnessEnabled');
            if ($brightnessEnabledID) {
                SetValue($brightnessEnabledID, true);
            }
            $brightnessThresholdID = @$this->GetIDForIdent('BrightnessThreshold');
            if ($brightnessThresholdID) {
                SetValue($brightnessThresholdID, $this->ReadPropertyInteger('BrightnessThreshold'));
            }
        }

        if ($enableAutoOff) {
            $this->EnableAction('AutoOffEnabled');
            $this->EnableAction('AutoOffTime');
            $this->EnableAction('ExtendTimer');
            $this->EnableAction('NotificationsEnabled');
            $this->EnableAction('NotificationThreshold');

            // Always apply config values to variables (config is source of truth)
            $autoOffEnabledID = @$this->GetIDForIdent('AutoOffEnabled');
            if ($autoOffEnabledID) {
                SetValue($autoOffEnabledID, true);
            }
            $autoOffTimeID = @$this->GetIDForIdent('AutoOffTime');
            if ($autoOffTimeID) {
                SetValue($autoOffTimeID, $this->ReadPropertyInteger('AutoOffTime'));
            }
            $notificationsEnabledID = @$this->GetIDForIdent('NotificationsEnabled');
            if ($notificationsEnabledID) {
                SetValue($notificationsEnabledID, $this->ReadPropertyBoolean('EnableNotifications'));
            }
            $notificationThresholdID = @$this->GetIDForIdent('NotificationThreshold');
            if ($notificationThresholdID) {
                SetValue($notificationThresholdID, $this->ReadPropertyInteger('NotificationThreshold'));
            }
        }

        // Set visibility based on configuration
        $this->updateVariableVisibility();

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

        if ($enableAutoOff) {
            @SetValue($this->GetIDForIdent('RemainingTime'), 0);
        }

        // Update master switch state based on current lamp states
        $this->updateMasterSwitchState();
    }

    /* ================= Configuration Form ================= */
    public function GetConfigurationForm(): string
    {
        $enableAutoOff = $this->ReadPropertyBoolean('EnableAutoOff');

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
                            'type' => 'CheckBox',
                            'name' => 'EnableLightSwitches',
                            'caption' => 'Enable Light Switches'
                        ],
                        [
                            'type' => 'Select',
                            'name' => 'SwitchMode',
                            'caption' => 'Switch Mode',
                            'visible' => $this->ReadPropertyBoolean('EnableLightSwitches'),
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
                            'visible' => $this->ReadPropertyBoolean('EnableLightSwitches'),
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
                            'type' => 'CheckBox',
                            'name' => 'EnablePresenceDetection',
                            'caption' => 'Enable Presence Detection'
                        ],
                        [
                            'type' => 'List',
                            'name' => 'PresenceDetectors',
                            'caption' => 'Presence Detectors',
                            'rowCount' => 5,
                            'add' => true,
                            'delete' => true,
                            'visible' => $this->ReadPropertyBoolean('EnablePresenceDetection'),
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
                        ],
                        [
                            'type' => 'NumberSpinner',
                            'name' => 'PresenceFollowUpTime',
                            'caption' => 'Follow-Up Time (s)',
                            'minimum' => 1,
                            'maximum' => 172800,
                            'visible' => $this->ReadPropertyBoolean('EnablePresenceDetection')
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Brightness Control',
                    'items' => [
                        [
                            'type' => 'CheckBox',
                            'name' => 'EnableBrightnessControl',
                            'caption' => 'Enable Brightness Control'
                        ],
                        [
                            'type' => 'SelectVariable',
                            'name' => 'BrightnessSensor',
                            'caption' => 'Brightness Sensor Variable',
                            'validVariableTypes' => [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT],
                            'visible' => $this->ReadPropertyBoolean('EnableBrightnessControl')
                        ],
                        [
                            'type' => 'NumberSpinner',
                            'name' => 'BrightnessThreshold',
                            'caption' => 'Brightness Threshold (lux)',
                            'minimum' => 0,
                            'visible' => $this->ReadPropertyBoolean('EnableBrightnessControl')
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Auto-Off Settings',
                    'items' => [
                        [
                            'type' => 'CheckBox',
                            'name' => 'EnableAutoOff',
                            'caption' => 'Enable Auto-Off Feature'
                        ],
                        [
                            'type' => 'NumberSpinner',
                            'name' => 'AutoOffTime',
                            'caption' => 'Auto-Off Time (s)',
                            'minimum' => 1,
                            'maximum' => 172800,
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'EnableNotifications',
                            'caption' => 'Enable Push Notifications',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'NumberSpinner',
                            'name' => 'NotificationThreshold',
                            'caption' => 'Notification Threshold (s)',
                            'minimum' => 1,
                            'maximum' => 172800,
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'ValidationTextBox',
                            'name' => 'LightName',
                            'caption' => 'Light Name',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'ValidationTextBox',
                            'name' => 'LightLocation',
                            'caption' => 'Light Location',
                            'visible' => $enableAutoOff
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Tile Visualizations',
                    'visible' => $enableAutoOff,
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
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'Visualization Visibility',
                    'items' => [
                        [
                            'type' => 'Label',
                            'caption' => 'Show/hide elements in visualization:'
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowMasterSwitch',
                            'caption' => 'Show Master Switch'
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowSwitchesToggle',
                            'caption' => 'Show Light Switches Toggle',
                            'visible' => $this->ReadPropertyBoolean('EnableLightSwitches')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowPresenceToggle',
                            'caption' => 'Show Presence Toggle',
                            'visible' => $this->ReadPropertyBoolean('EnablePresenceDetection')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowPresenceFollowUpTime',
                            'caption' => 'Show Presence Follow-Up Time',
                            'visible' => $this->ReadPropertyBoolean('EnablePresenceDetection')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowBrightnessToggle',
                            'caption' => 'Show Brightness Toggle',
                            'visible' => $this->ReadPropertyBoolean('EnableBrightnessControl')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowBrightnessThreshold',
                            'caption' => 'Show Brightness Threshold',
                            'visible' => $this->ReadPropertyBoolean('EnableBrightnessControl')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowAutoOffToggle',
                            'caption' => 'Show Auto-Off Toggle',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowAutoOffTime',
                            'caption' => 'Show Auto-Off Time Setting',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowRemainingTime',
                            'caption' => 'Show Remaining Time',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowExtendButton',
                            'caption' => 'Show Extend Timer Button',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowNotificationToggle',
                            'caption' => 'Show Notification Toggle',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'ShowNotificationThreshold',
                            'caption' => 'Show Notification Threshold',
                            'visible' => $enableAutoOff
                        ]
                    ]
                ],
                [
                    'type' => 'ExpansionPanel',
                    'caption' => 'User Configuration Permissions',
                    'items' => [
                        [
                            'type' => 'Label',
                            'caption' => 'User permissions in visualization:'
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserSwitchesToggle',
                            'caption' => 'Allow Light Switches Toggle',
                            'visible' => $this->ReadPropertyBoolean('EnableLightSwitches')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserPresenceToggle',
                            'caption' => 'Allow Presence Toggle',
                            'visible' => $this->ReadPropertyBoolean('EnablePresenceDetection')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserBrightnessToggle',
                            'caption' => 'Allow Brightness Toggle',
                            'visible' => $this->ReadPropertyBoolean('EnableBrightnessControl')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserBrightnessThreshold',
                            'caption' => 'Allow Brightness Threshold',
                            'visible' => $this->ReadPropertyBoolean('EnableBrightnessControl')
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserAutoOffToggle',
                            'caption' => 'Allow Auto-Off Toggle',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserAutoOffTime',
                            'caption' => 'Allow Auto-Off Time',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserExtend',
                            'caption' => 'Allow Extending Timer',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserNotificationToggle',
                            'caption' => 'Allow Notifications Toggle',
                            'visible' => $enableAutoOff
                        ],
                        [
                            'type' => 'CheckBox',
                            'name' => 'AllowUserNotificationThreshold',
                            'caption' => 'Allow Notification Threshold',
                            'visible' => $enableAutoOff
                        ]
                    ]
                ]
            ],
            'actions' => [
                [
                    'type' => 'Button',
                    'caption' => 'Switch All Lamps ON',
                    'onClick' => 'ALC_SwitchAll($id, true);'
                ],
                [
                    'type' => 'Button',
                    'caption' => 'Switch All Lamps OFF',
                    'onClick' => 'ALC_SwitchAll($id, false);'
                ],
                [
                    'type' => 'Button',
                    'caption' => 'Reset Timer',
                    'onClick' => 'ALC_ExtendTimer($id);',
                    'visible' => $enableAutoOff
                ]
            ],
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
                if ($this->ReadPropertyBoolean('AllowUserAutoOffToggle')) {
                    SetValue($this->GetIDForIdent('AutoOffEnabled'), (bool)$Value);
                    if (!(bool)$Value) {
                        $this->stopTimer();
                    }
                }
                break;

            case 'AutoOffTime':
                if ($this->ReadPropertyBoolean('AllowUserAutoOffTime')) {
                    $val = max(1, min(172800, (int)$Value));
                    SetValue($this->GetIDForIdent('AutoOffTime'), $val);
                    $this->syncPropertyToConfig('AutoOffTime', $val);
                }
                break;

            case 'ExtendTimer':
                if ($this->ReadPropertyBoolean('AllowUserExtend')) {
                    $this->ExtendTimer();
                }
                SetValue($this->GetIDForIdent('ExtendTimer'), 0);
                break;

            case 'NotificationsEnabled':
                if ($this->ReadPropertyBoolean('AllowUserNotificationToggle')) {
                    SetValue($this->GetIDForIdent('NotificationsEnabled'), (bool)$Value);
                    $this->syncPropertyToConfig('EnableNotifications', (bool)$Value);
                }
                break;

            case 'NotificationThreshold':
                if ($this->ReadPropertyBoolean('AllowUserNotificationThreshold')) {
                    $val = max(1, min(172800, (int)$Value));
                    SetValue($this->GetIDForIdent('NotificationThreshold'), $val);
                    $this->syncPropertyToConfig('NotificationThreshold', $val);
                }
                break;

            case 'PresenceEnabled':
                if ($this->ReadPropertyBoolean('AllowUserPresenceToggle')) {
                    SetValue($this->GetIDForIdent('PresenceEnabled'), (bool)$Value);
                }
                break;

            case 'PresenceFollowUpTime':
                if ($this->ReadPropertyBoolean('AllowUserPresenceToggle')) {
                    $val = max(1, min(172800, (int)$Value));
                    SetValue($this->GetIDForIdent('PresenceFollowUpTime'), $val);
                    $this->syncPropertyToConfig('PresenceFollowUpTime', $val);
                }
                break;

            case 'BrightnessEnabled':
                if ($this->ReadPropertyBoolean('AllowUserBrightnessToggle')) {
                    SetValue($this->GetIDForIdent('BrightnessEnabled'), (bool)$Value);
                }
                break;

            case 'BrightnessThreshold':
                if ($this->ReadPropertyBoolean('AllowUserBrightnessThreshold')) {
                    $val = max(0, (int)$Value);
                    SetValue($this->GetIDForIdent('BrightnessThreshold'), $val);
                    $this->syncPropertyToConfig('BrightnessThreshold', $val);
                }
                break;

            case 'SwitchesEnabled':
                if ($this->ReadPropertyBoolean('AllowUserSwitchesToggle')) {
                    SetValue($this->GetIDForIdent('SwitchesEnabled'), (bool)$Value);
                }
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

        // Check if sender is a presence detector (only if feature enabled)
        if ($this->ReadPropertyBoolean('EnablePresenceDetection')) {
            $detectors = $this->getPresenceDetectors();
            foreach ($detectors as $detector) {
                if ((int)$detector['DetectorID'] === $SenderID) {
                    $this->handlePresenceChange();
                    return;
                }
            }
        }

        // Check if sender is the brightness sensor (only if feature enabled)
        if ($this->ReadPropertyBoolean('EnableBrightnessControl')) {
            $brightnessSensorID = $this->ReadPropertyInteger('BrightnessSensor');
            if ($SenderID === $brightnessSensorID) {
                $this->handleBrightnessChange();
                return;
            }
        }

        // Check if sender is a light switch (only if feature enabled)
        if ($this->ReadPropertyBoolean('EnableLightSwitches')) {
            $switches = $this->getLightSwitches();
            foreach ($switches as $switch) {
                if ((int)$switch['SwitchID'] === $SenderID) {
                    $this->handleSwitchChange($SenderID, $Data);
                    return;
                }
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
        if (!$this->ReadPropertyBoolean('EnableAutoOff')) {
            return;
        }

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
        if (!$this->ReadPropertyBoolean('EnableAutoOff')) {
            return 0;
        }

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
        if (!$this->ReadPropertyBoolean('EnableAutoOff')) {
            return;
        }

        $seconds = max(1, min(86400, $Seconds));
        SetValue($this->GetIDForIdent('AutoOffTime'), $seconds);
    }

    /**
     * Enable or disable auto-off
     */
    public function SetAutoOffEnabled(bool $Enabled): void
    {
        if (!$this->ReadPropertyBoolean('EnableAutoOff')) {
            return;
        }

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
        if ($this->ReadPropertyBoolean('EnablePresenceDetection')) {
            $detectors = $this->getPresenceDetectors();
            foreach ($detectors as $detector) {
                $detectorID = (int)$detector['DetectorID'];
                if ($detectorID > 0) {
                    $this->RegisterMessage($detectorID, self::VM_UPDATE);
                }
            }
        }

        // Register brightness sensor variable
        if ($this->ReadPropertyBoolean('EnableBrightnessControl')) {
            $brightnessSensorID = $this->ReadPropertyInteger('BrightnessSensor');
            if ($brightnessSensorID > 0 && @IPS_VariableExists($brightnessSensorID)) {
                $this->RegisterMessage($brightnessSensorID, self::VM_UPDATE);
            }
        }

        // Register light switch variables
        if ($this->ReadPropertyBoolean('EnableLightSwitches')) {
            $switches = $this->getLightSwitches();
            foreach ($switches as $switch) {
                $switchID = (int)$switch['SwitchID'];
                if ($switchID > 0) {
                    $this->RegisterMessage($switchID, self::VM_UPDATE);
                }
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
        if (!$this->ReadPropertyBoolean('EnablePresenceDetection')) {
            return false;
        }

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
        if (!$this->ReadPropertyBoolean('EnablePresenceDetection')) {
            return false;
        }

        $presenceEnabledID = @$this->GetIDForIdent('PresenceEnabled');
        if ($presenceEnabledID && @IPS_VariableExists($presenceEnabledID)) {
            return (bool)@GetValue($presenceEnabledID);
        }
        return true;
    }

    private function isBrightnessBelowThreshold(): bool
    {
        if (!$this->ReadPropertyBoolean('EnableBrightnessControl')) {
            return true; // If brightness control is disabled in config, always allow
        }

        // Check if brightness control is enabled via visualization variable
        $brightnessEnabledID = @$this->GetIDForIdent('BrightnessEnabled');
        if ($brightnessEnabledID && @IPS_VariableExists($brightnessEnabledID)) {
            if (!(bool)@GetValue($brightnessEnabledID)) {
                return true; // Brightness control disabled by user, always allow
            }
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
        return (float)max(0, $this->ReadPropertyInteger('BrightnessThreshold'));
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
        return max(1, $this->ReadPropertyInteger('PresenceFollowUpTime'));
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
        // Check if switches are enabled in config
        if (!$this->ReadPropertyBoolean('EnableLightSwitches')) {
            return;
        }

        // Check if switches are enabled via visualization variable
        $switchesEnabledID = @$this->GetIDForIdent('SwitchesEnabled');
        if ($switchesEnabledID && @IPS_VariableExists($switchesEnabledID)) {
            if (!(bool)@GetValue($switchesEnabledID)) {
                return; // Switches disabled by user
            }
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
        if (!$this->ReadPropertyBoolean('EnableAutoOff')) {
            return false;
        }

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
        return max(1, $this->ReadPropertyInteger('AutoOffTime'));
    }

    private function areNotificationsEnabled(): bool
    {
        if (!$this->ReadPropertyBoolean('EnableAutoOff')) {
            return false;
        }

        $notificationsEnabledID = @$this->GetIDForIdent('NotificationsEnabled');
        if ($notificationsEnabledID && @IPS_VariableExists($notificationsEnabledID)) {
            return (bool)@GetValue($notificationsEnabledID);
        }
        return $this->ReadPropertyBoolean('EnableNotifications');
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
        return max(1, $this->ReadPropertyInteger('NotificationThreshold'));
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
     * Sync a user-changed value back to the instance configuration
     * Preserves timer state across the ApplyChanges call
     */
    private function syncPropertyToConfig(string $propertyName, $value): void
    {
        // Check if value is different from current property
        if (is_bool($value)) {
            $currentValue = $this->ReadPropertyBoolean($propertyName);
        } elseif (is_int($value)) {
            $currentValue = $this->ReadPropertyInteger($propertyName);
        } else {
            $currentValue = $this->ReadPropertyString($propertyName);
        }

        if ($currentValue === $value) {
            return; // No change needed
        }

        // Save timer state before ApplyChanges
        $autoOffUntil = $this->ReadAttributeInteger('AutoOffUntil');
        $notificationSent = $this->ReadAttributeBoolean('NotificationSent');
        $countdownRunning = $autoOffUntil > time();

        // Apply property change
        IPS_SetProperty($this->InstanceID, $propertyName, $value);
        IPS_ApplyChanges($this->InstanceID);

        // Restore timer state if it was running
        if ($countdownRunning && $autoOffUntil > time()) {
            $this->WriteAttributeInteger('AutoOffUntil', $autoOffUntil);
            $this->WriteAttributeBoolean('NotificationSent', $notificationSent);
            $remaining = $autoOffUntil - time();
            $this->SetTimerInterval('AutoOff', $remaining * 1000);
            $this->SetTimerInterval('CountdownTick', 1000);
            @SetValue($this->GetIDForIdent('RemainingTime'), $remaining);
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

    private function updateVariableVisibility(): void
    {
        $enableAutoOff = $this->ReadPropertyBoolean('EnableAutoOff');
        $enablePresence = $this->ReadPropertyBoolean('EnablePresenceDetection');
        $enableBrightness = $this->ReadPropertyBoolean('EnableBrightnessControl');

        // Master switch visibility
        $masterSwitchID = @$this->GetIDForIdent('MasterSwitch');
        if ($masterSwitchID) {
            IPS_SetHidden($masterSwitchID, !$this->ReadPropertyBoolean('ShowMasterSwitch'));
        }

        // Presence detection toggle visibility (independent of auto-off)
        if ($enablePresence) {
            $presenceEnabledID = @$this->GetIDForIdent('PresenceEnabled');
            if ($presenceEnabledID) {
                $show = $this->ReadPropertyBoolean('ShowPresenceToggle');
                IPS_SetHidden($presenceEnabledID, !$show);

                if ($show && !$this->ReadPropertyBoolean('AllowUserPresenceToggle')) {
                    $this->DisableAction('PresenceEnabled');
                }
            }

            // Presence follow-up time visibility
            $followUpTimeID = @$this->GetIDForIdent('PresenceFollowUpTime');
            if ($followUpTimeID) {
                $show = $this->ReadPropertyBoolean('ShowPresenceFollowUpTime');
                IPS_SetHidden($followUpTimeID, !$show);

                if ($show && !$this->ReadPropertyBoolean('AllowUserPresenceToggle')) {
                    $this->DisableAction('PresenceFollowUpTime');
                }
            }
        }

        // Brightness control visibility (independent of auto-off)
        if ($enableBrightness) {
            // Brightness toggle visibility
            $brightnessEnabledID = @$this->GetIDForIdent('BrightnessEnabled');
            if ($brightnessEnabledID) {
                $show = $this->ReadPropertyBoolean('ShowBrightnessToggle');
                IPS_SetHidden($brightnessEnabledID, !$show);

                if ($show && !$this->ReadPropertyBoolean('AllowUserBrightnessToggle')) {
                    $this->DisableAction('BrightnessEnabled');
                }
            }

            // Brightness threshold visibility
            $brightnessThresholdID = @$this->GetIDForIdent('BrightnessThreshold');
            if ($brightnessThresholdID) {
                $show = $this->ReadPropertyBoolean('ShowBrightnessThreshold');
                IPS_SetHidden($brightnessThresholdID, !$show);

                if ($show && !$this->ReadPropertyBoolean('AllowUserBrightnessThreshold')) {
                    $this->DisableAction('BrightnessThreshold');
                }
            }
        }

        // Light switches toggle visibility (independent of auto-off)
        $enableSwitches = $this->ReadPropertyBoolean('EnableLightSwitches');
        if ($enableSwitches) {
            $switchesEnabledID = @$this->GetIDForIdent('SwitchesEnabled');
            if ($switchesEnabledID) {
                $show = $this->ReadPropertyBoolean('ShowSwitchesToggle');
                IPS_SetHidden($switchesEnabledID, !$show);

                if ($show && !$this->ReadPropertyBoolean('AllowUserSwitchesToggle')) {
                    $this->DisableAction('SwitchesEnabled');
                }
            }
        }

        if (!$enableAutoOff) {
            return;
        }

        // Auto-off toggle visibility
        $autoOffEnabledID = @$this->GetIDForIdent('AutoOffEnabled');
        if ($autoOffEnabledID) {
            $show = $this->ReadPropertyBoolean('ShowAutoOffToggle');
            IPS_SetHidden($autoOffEnabledID, !$show);

            // Disable action if user not allowed to change
            if ($show && !$this->ReadPropertyBoolean('AllowUserAutoOffToggle')) {
                $this->DisableAction('AutoOffEnabled');
            }
        }

        // Auto-off time visibility
        $autoOffTimeID = @$this->GetIDForIdent('AutoOffTime');
        if ($autoOffTimeID) {
            $show = $this->ReadPropertyBoolean('ShowAutoOffTime');
            IPS_SetHidden($autoOffTimeID, !$show);

            // Disable action if user not allowed to change
            if ($show && !$this->ReadPropertyBoolean('AllowUserAutoOffTime')) {
                $this->DisableAction('AutoOffTime');
            }
        }

        // Remaining time visibility
        $remainingTimeID = @$this->GetIDForIdent('RemainingTime');
        if ($remainingTimeID) {
            IPS_SetHidden($remainingTimeID, !$this->ReadPropertyBoolean('ShowRemainingTime'));
        }

        // Extend button visibility
        $extendTimerID = @$this->GetIDForIdent('ExtendTimer');
        if ($extendTimerID) {
            $show = $this->ReadPropertyBoolean('ShowExtendButton');
            IPS_SetHidden($extendTimerID, !$show);

            // Disable action if user not allowed
            if ($show && !$this->ReadPropertyBoolean('AllowUserExtend')) {
                $this->DisableAction('ExtendTimer');
            }
        }

        // Notification toggle visibility
        $notificationsEnabledID = @$this->GetIDForIdent('NotificationsEnabled');
        if ($notificationsEnabledID) {
            $show = $this->ReadPropertyBoolean('ShowNotificationToggle');
            IPS_SetHidden($notificationsEnabledID, !$show);

            // Disable action if user not allowed to change
            if ($show && !$this->ReadPropertyBoolean('AllowUserNotificationToggle')) {
                $this->DisableAction('NotificationsEnabled');
            }
        }

        // Notification threshold visibility
        $notificationThresholdID = @$this->GetIDForIdent('NotificationThreshold');
        if ($notificationThresholdID) {
            $show = $this->ReadPropertyBoolean('ShowNotificationThreshold');
            IPS_SetHidden($notificationThresholdID, !$show);

            // Disable action if user not allowed to change
            if ($show && !$this->ReadPropertyBoolean('AllowUserNotificationThreshold')) {
                $this->DisableAction('NotificationThreshold');
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
