# AdvancedLightControl for IP-Symcon

[![IP-Symcon Version](https://img.shields.io/badge/IP--Symcon-8.1+-blue.svg)](https://www.symcon.de)
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
  - [Notifications](#notifications)
  - [Tile Visualizations](#tile-visualizations)
- [Variables](#variables)
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
- **User-Controlled Features**:
  - All features can be enabled/disabled by users via variables in the visualization
  - All settings adjustable at runtime without configuration changes

---

## Requirements

- IP-Symcon 8.1 or higher
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
| **Switch Mode** | How switches are interpreted (see below) |
| **Switch Variable** | Select a boolean variable from a switch |
| **Name** | Optional friendly name for identification |

**Switch Modes:**
- **Push-button**: First press turns on, second press turns off
- **Toggle on any change**: Any state change toggles lights
- **On-only (staircase)**: Only turns lights on (useful with auto-off)

**Note:** Enable/disable light switches via the "Light Switches" variable in the visualization.

### Presence Detection

Configure presence detectors for automatic light control:

| Setting | Description |
|---------|-------------|
| **Presence Detectors** | List of boolean presence detector variables |

**Behavior:**
- Lights turn on when ANY detector reports presence (OR logic)
- Lights turn off after follow-up time when ALL detectors report no presence
- Manual switch-on is tracked separately (presence won't turn off manually activated lights)
- After auto-off, presence must go false before it can trigger lights again

**Note:** Enable/disable and adjust follow-up time via variables in the visualization.

### Brightness Control

Configure the brightness sensor:

| Setting | Description |
|---------|-------------|
| **Brightness Sensor Variable** | Integer or float variable with lux value |

**Note:** Enable/disable and adjust threshold via variables in the visualization.

### Notifications

Configure notification settings for auto-off warnings:

| Setting | Description |
|---------|-------------|
| **Light Name** | Name for push notifications (e.g., "Ceiling Light") |
| **Light Location** | Location for push notifications (e.g., "Living Room") |

### Tile Visualizations

Register Tile Visualization instances for push notifications:

| Setting | Description |
|---------|-------------|
| **Tile Visualization** | Select a Tile Visualization instance |

You can register multiple Tile Visualizations. All registered visualizations will receive push notifications when the remaining time drops below the configured threshold.

---

## Variables

The module creates the following variables (all always available):

| Variable | Type | Description |
|----------|------|-------------|
| **All Lights** | Boolean | Master switch to control all lamps |
| **Light Switches** | Boolean | Enable/disable light switch feature |
| **Presence Detection** | Boolean | Enable/disable presence detection |
| **Presence Follow-Up Time** | Integer | Seconds to wait after presence ends (default: 60) |
| **Brightness Control** | Boolean | Enable/disable brightness control |
| **Brightness Threshold** | Integer | Lux threshold for presence activation (default: 100) |
| **Auto-Off** | Boolean | Enable/disable auto-off feature |
| **Auto-Off Time** | Integer | Timeout in seconds (default: 300) |
| **Remaining Time** | Integer | Countdown display |
| **Extend Timer** | Integer (Button) | Reset/extend the timer |
| **Notifications** | Boolean | Enable/disable push notifications |
| **Notify Before** | Integer | Seconds before auto-off to send notification (default: 60) |

All feature toggles are disabled by default. Users can enable/disable features and adjust settings directly via the visualization without needing access to the instance configuration.

### Push Notifications

When configured with Tile Visualizations and notifications enabled:

1. Lights turn on (manually, via switch, or by presence)
2. Auto-off timer starts counting down
3. When remaining time reaches the "Notify Before" threshold, a push notification is sent
4. Notification shows: **"Light Name (Location)"** - "Turns off in X seconds. Tap to extend."
5. User can tap the notification to extend the timer
6. If not extended, lights automatically switch off when timer reaches zero

**Note**: Auto-off takes priority over presence detection. When auto-off triggers, presence must go false before it can turn lights on again.

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

### Version 2.0.0
- **Breaking Change**: Removed Enable* checkboxes from instance configuration
- All variables are now always created (no conditional creation)
- Features are now enabled/disabled via variables in the visualization
- Removed Visualization Visibility settings (all variables always visible)
- Removed User Configuration Permissions (all settings user-adjustable)
- Cleaner variable names (removed "Enabled" suffix from toggle variables)
- Renamed "Notification Threshold" to "Notify Before" for clarity

### Version 1.0.0
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
