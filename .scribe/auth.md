# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your authentication token by logging in via the <code>POST /api/login</code> endpoint using your contact number and password. Use this token in the Authorization header as: <code>Authorization: Bearer {token}</code>
