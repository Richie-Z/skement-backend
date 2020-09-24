# About Trilu

Trilu, task management website

## Future

### 1. Authentication
 1. Register <br/>
URL: /v1/auth/register
 1. Login <br/>
URL: /v1/auth/login
 1. Logout <br/>
URL: /v1/auth/logout
### 2. Board
1. Create new board <br/>
URL: /v1/board
1. Update board <br/>
URL: /v1/board/{board_id}
1. Delete board <br/>
URL: /v1/board/{board_id}
1. Get all boards <br/>
URL: /v1/board
1. Open board <br/>
URL: /v1/board/{board_id}
1. Add team member <br/>
URL: /v1/board/{board_id}/member
1. Remove team member <br/>
URL:
/v1/board/{board_id}/member/{user_id}

### 3. List
1. Create new list <br/>
URL: /v1/board/{board_id}/list
1. Update list <br/>
URL: /v1/board/{board_id}/list/{list_id}
1. Delete list <br/>
URL: /v1/board/{board_id}/list/{list_id}
1. Move list to right <br/>
URL:
/v1/board/{board_id}/list/{list_id}/right
1. Move list to left <br/>
URL:
/v1/board/{board_id}/list/{list_id}/left

### 4. Card
1. Create new card <br/>
URL:/v1/board/{board_id}/list/{list_id}/card
1. Update card <br/>
URL: /v1/board/{board_id}/list/{list_id}/card/{card_id}
1. Delete card <br/>
URL: /v1/board/{board_id}/list/{list_id}/card/{card_id}
OKEN}
1. Move up card<br/>
URL: /v1/card/{card_id}/up
1. Move down card<br/>
URL: /v1/card/{card_id}/down
1. Move card to another list<br/>
URL: /v1/card/{card_id}/move/{list_id}


## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
