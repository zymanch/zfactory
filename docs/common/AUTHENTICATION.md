# Authentication System

## Login Flow
1. User visits landing page (`/`)
2. Clicks "ВОЙТИ!" button
3. Auto-login as user_id=1 (demo mode)
4. Redirect to game (`/game`)

## User Model
Implements Yii2 `IdentityInterface`:
- `findIdentity($id)` - find by ID
- `findIdentityByAccessToken($token)` - find by auth_key
- `getId()`, `getAuthKey()`, `validateAuthKey()`
- `getBuildPanelArray()` - get 10-slot array from JSON
- `setBuildPanelArray($array)` - save array as JSON

## Routes

| Route                   | Action Class                  | Description              |
|-------------------------|-------------------------------|--------------------------|
| `/`                     | `actions\site\Index`          | Landing page             |
| `/site/login`           | `actions\site\Login`          | Auto-login (demo)        |
| `/site/logout`          | `actions\site\Logout`         | Logout                   |
| `/game`                 | `actions\game\Index`          | Game page                |
| `/game/config`          | `actions\game\Config`         | Game configuration       |
| `/game/entities`        | `actions\game\Entities`       | Load entities            |
| `/game/delete-entity`   | `actions\game\DeleteEntity`   | Delete entity            |
| `/map/tiles`            | `actions\map\Tiles`           | Load terrain tiles       |
| `/map/create-entity`    | `actions\map\CreateEntity`    | Place building           |
| `/user/save-build-panel`| `actions\user\SaveBuildPanel` | Save build panel slots   |
