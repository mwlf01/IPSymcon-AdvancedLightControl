# AdvancedLightControl for IP-Symcon

[![IP-Symcon Version](https://img.shields.io/badge/IP--Symcon-7.0+-blue.svg)](https://www.symcon.de)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A powerful IP-Symcon module for centralized control of multiple lights with automatic switch-off, presence detection, brightness control, and Tile Visualization integration.

**[Deutsche Version](README.de.md)**

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Lamps](#lamps)
  - [Light Switches](#light-switches)
  - [Presence Detection](#presence-detection)
  - [Brightness Control](#brightness-control)
  - [Auto-Off Settings](#auto-off-settings)
  - [Tile Visualizations](#tile-visualizations)
  - [Visualization Visibility](#visualization-visibility)
  - [User Configuration Permissions](#user-configuration-permissions)
- [Visualization](#visualization)
- [PHP Functions](#php-functions)
- [License](#license)

---

## Features

- **Group Light Control**: Register any number of boolean variables (lamps) and control them all with a single master switch
- **Light Switches**: Connect physical switches with three modes:
  - Push-button (toggle on press)
  - Toggle on any change
  - On-only (staircase lighting)
- **Presence Detection**: 
  - Automatic light control based on presence detectors
  - Configurable follow-up time after presence ends
  - Multiple presence detectors supported (OR logic)
  - Smart handling of manual vs. presence-triggered activations
- **Brightness Control**:
  - Only turn on lights when brightness is below threshold
  - Supports integer and float brightness sensors
  - User-adjustable threshold via visualization
- **Auto-Off Timer**: 
  - Automatic switch-off with configurable timeout (1 second to 48 hours)
  - Auto-off takes priority over presence detection
- **Push Notifications**: 
  - Notifications before auto-off via Tile Visualization
  - Customizable light name and location in notifications
  - Support for multiple Tile Visualizations
- **Flexible Visibility & Permissions**:
  - Show or hide individual controls in the visualization
  - Per-feature user configuration permissions
  - Conditional variables (only created when feature is enabled)

---

## Requirements

- IP-Symcon 7.0 or higher
- Valid IP-Symcon subscription for push notifications (optional)

---

## Installation

### Via Module Store (Recommended)

1. Open IP-Symcon Console
2. Navigate to **Modules** > **Module Store**
3. Search for "AdvancedLightControl"
4. Click **Install**

### Manual Installation via Git

1. Open IP-Symcon Console
2. Navigate to **Modules** > **Modules**
3. Click **Add** (Plus icon)
4. Select **Add Module from URL**
5. Enter: `https://github.com/mwlf01/IPSymcon-AdvancedLightControl.git`
6. Click **OK**

### Manual Installation (File Copy)

1. Clone or download this repository
2. Copy the folder to your IP-Symcon modules directory:
   - Windows: `C:\ProgramData\Symcon\modules\`
   - Linux: `/var/lib/symcon/modules/`
   - Docker: Check your volume mapping
3. Reload modules in IP-Symcon Console

---

## Configuration

After installation, create a new instance:

1. Navigate to **Objects** > **Add Object** > **Instance**
2. Search for "AdvancedLightControl" or "Advanced Light Control"
3. Click **OK** to create the instance

### Lamps

Register boolean variables that represent your lamps:

| Setting | Description |
|---------|-------------|
| **Lamp Variable** | Select a boolean variable that controls a lamp |
| **Name** | Optional friendly name for identification |

You can add as many lamps as needed. All registered lamps will be switched on/off together using the master switch.

### Light Switches

Connect physical switches to control the lights:

| Setting | Description |
|---------|-------------|
| **Enable Light Switches** | Enable/disable the light switch feature |
| **Switch Mode** | How switches are interpreted (see below) |
| **Switch Variable** | Select a boolean variable from a switch |
| **Name** | Optional friendly name for identification |

**Switch Modes:**
- **Push-button**: First press turns on, second press turns off
- **Toggle on any change**: Any state change toggles lights
- **On-only (staircase)**: Only turns lights on (useful with auto-off)

### Presence Detection

Automatically control lights based on presence:

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Presence Detection** | Enable/disable presence-based control | Off |
| **Presence Detectors** | List of boolean presence detector variables | - |
| **Follow-Up Time (s)** | Seconds to wait after presence ends before turning off | 60 |

**Behavior:**
- Lights turn on when ANY detector reports presence (OR logic)
- Lights turn off after follow-up time when ALL detectors report no presence
- Manual switch-on is tracked separately (presence won't turn off manually activated lights)
- After auto-off, presence must go false before it can trigger lights again

### Brightness Control

Only turn on lights when it's dark enough:

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Brightness Control** | Enable/disable brightness-based control | Off |
| **Brightness Sensor Variable** | Integer or float variable with lux value | - |
| **Brightness Threshold (lux)** | Lights only turn on below this value | 100 |

### Auto-Off Settings

Configure the automatic switch-off feature:

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Auto-Off Feature** | Enable/disable the entire auto-off functionality | Off |
| **Auto-Off Time (s)** | Time in seconds until automatic switch-off (1-172800) | 300 |
| **Enable Push Notifications** | Send notifications before auto-off triggers | Off |
| **Notification Threshold (s)** | Seconds before auto-off to send notification | 60 |
| **Light Name** | Name for push notifications (e.g., "Ceiling Light") | - |
| **Light Location** | Location for push notifications (e.g., "Living Room") | - |

**Note**: Auto-off takes priority over presence detection. When auto-off triggers, presence must go false before it can turn lights on again.

### Tile Visualizations

Register Tile Visualization instances for push notifications:

| Setting | Description |
|---------|-------------|
| **Tile Visualization** | Select a Tile Visualization instance |

You can register multiple Tile Visualizations. All registered visualizations will receive push notifications when the remaining time drops below the configured threshold.

### Visualization Visibility

Control which elements are visible in the visualization:

| Setting | Description |
|---------|-------------|
| **Show Master Switch** | Display the main on/off switch |
| **Show Light Switches Toggle** | Display the switches enable/disable toggle |
| **Show Presence Toggle** | Display the presence detection toggle |
| **Show Presence Follow-Up Time** | Display the follow-up time setting |
| **Show Brightness Toggle** | Display the brightness control toggle |
| **Show Brightness Threshold** | Display the brightness threshold setting |
| **Show Auto-Off Toggle** | Display the auto-off enable/disable toggle |
| **Show Auto-Off Time Setting** | Display the timeout configuration |
| **Show Remaining Time** | Display the countdown timer |
| **Show Extend Timer Button** | Display the timer reset button |
| **Show Notification Toggle** | Display the notification toggle |
| **Show Notification Threshold** | Display the notification threshold setting |

### User Configuration Permissions

Control what users can change via the visualization:

| Setting | Description |
|---------|-------------|
| **Allow Light Switches Toggle** | Users can enable/disable light switches |
| **Allow Presence Toggle** | Users can enable/disable presence detection |
| **Allow Brightness Toggle** | Users can enable/disable brightness control |
| **Allow Brightness Threshold** | Users can adjust the brightness threshold |
| **Allow Auto-Off Toggle** | Users can toggle auto-off on/off |
| **Allow Auto-Off Time** | Users can adjust the timeout |
| **Allow Extending Timer** | Users can reset/extend the timer |
| **Allow Notifications Toggle** | Users can toggle notifications on/off |
| **Allow Notification Threshold** | Users can adjust the notification threshold |

When a permission is disabled, the control is visible but read-only.

**Note:** User changes made via visualization are automatically synced back to the instance configuration. This means changes persist and are not lost when the admin makes other configuration changes.

---

## Visualization

The module creates the following variables (conditional based on enabled features):

| Variable | Type | Description |
|----------|------|-------------|
| **All Lights** | Boolean | Master switch to control all lamps |
| **Light Switches Enabled** | Boolean | Toggle for light switch feature |
| **Presence Enabled** | Boolean | Toggle for presence detection |
| **Presence Follow-Up Time** | Integer | Seconds to wait after presence ends |
| **Brightness Enabled** | Boolean | Toggle for brightness control |
| **Brightness Threshold** | Integer | Lux threshold for presence activation |
| **Auto-Off Enabled** | Boolean | Toggle for auto-off feature |
| **Auto-Off Time** | Integer | Timeout in seconds |
| **Remaining Time** | Integer | Countdown display |
| **Extend Timer** | Integer (Button) | Reset/extend the timer |
| **Notifications Enabled** | Boolean | Toggle for push notifications |
| **Notification Threshold** | Integer | Seconds before auto-off to send notification |

### Push Notifications

When configured with Tile Visualizations and push notifications enabled:

1. Lights turn on (manually, via switch, or by presence)
2. Auto-off timer starts counting down
3. When remaining time reaches the notification threshold, a push notification is sent
4. Notification shows: **"Light Name (Location)"** - "Turns off in X seconds. Tap to extend."
5. User can tap the notification to extend the timer
6. If not extended, lights automatically switch off when timer reaches zero

---

## PHP Functions

The module provides the following public functions for use in scripts:

### SwitchAll

Switch all registered lamps on or off.

```php
ALC_SwitchAll(int $InstanceID, bool $State);
```

**Parameters:**
- `$InstanceID` - ID of the AdvancedLightControl instance
- `$State` - `true` to switch on, `false` to switch off

**Example:**
```php
// Switch all lamps on
ALC_SwitchAll(12345, true);

// Switch all lamps off
ALC_SwitchAll(12345, false);
```

### ExtendTimer

Reset/extend the auto-off timer to the configured timeout value.

```php
ALC_ExtendTimer(int $InstanceID);
```

**Parameters:**
- `$InstanceID` - ID of the AdvancedLightControl instance

**Example:**
```php
// Extend the timer
ALC_ExtendTimer(12345);
```

### GetRemainingTime

Get the current remaining time until auto-off in seconds.

```php
int ALC_GetRemainingTime(int $InstanceID);
```

**Parameters:**
- `$InstanceID` - ID of the AdvancedLightControl instance

**Returns:** Remaining time in seconds (0 if timer not running)

**Example:**
```php
$remaining = ALC_GetRemainingTime(12345);
echo "Lights will turn off in $remaining seconds";
```

### SetAutoOffTime

Set the auto-off timeout value.

```php
ALC_SetAutoOffTime(int $InstanceID, int $Seconds);
```

**Parameters:**
- `$InstanceID` - ID of the AdvancedLightControl instance
- `$Seconds` - Timeout in seconds (1-86400)

**Example:**
```php
// Set auto-off to 10 minutes
ALC_SetAutoOffTime(12345, 600);
```

### SetAutoOffEnabled

Enable or disable the auto-off feature.

```php
ALC_SetAutoOffEnabled(int $InstanceID, bool $Enabled);
```

**Parameters:**
- `$InstanceID` - ID of the AdvancedLightControl instance
- `$Enabled` - `true` to enable, `false` to disable

**Example:**
```php
// Disable auto-off
ALC_SetAutoOffEnabled(12345, false);
```

### AutoOff (Internal)

Timer callback for auto-off. Generally not called directly.

```php
ALC_AutoOff(int $InstanceID);
```

### CountdownTick (Internal)

Timer callback for countdown updates. Generally not called directly.

```php
ALC_CountdownTick(int $InstanceID);
```

---

## Changelog

### Version 1.0.0 (2026-01-11)
- Initial release
- Group light control with master switch
- Light switch support with three modes (push-button, toggle, on-only)
- Presence detection with multiple detectors and follow-up time
- Brightness control with configurable lux threshold
- Auto-off timer with configurable timeout (1 second to 48 hours)
- Push notifications via Tile Visualization with customizable text
- Bidirectional sync: user changes via visualization are saved to instance config
- Flexible visibility and permission controls
- Full German localization (UI, variables, notifications)

---

## Support

For issues, feature requests, or contributions, please visit:
- [GitHub Repository](https://github.com/mwlf01/IPSymcon-AdvancedLightControl)
- [GitHub Issues](https://github.com/mwlf01/IPSymcon-AdvancedLightControl/issues)

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## Author

**mwlf01**

- GitHub: [@mwlf01](https://github.com/mwlf01)
