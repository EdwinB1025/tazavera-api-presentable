# Introduction

REST API for the Tazavera specialty coffee platform. Provides endpoints for managing coffee offerings, sensory evaluations (cupping), user profiles, and supporting taxonomies. Authentication is handled via Laravel Passport (OAuth 2.0) using the Authorization Code flow with PKCE for first-party clients and the Password grant for trusted clients.

<aside>
    <strong>Base URL</strong>: <code>https://localhost:8443</code>
</aside>

    This documentation aims to provide all the information you need to work with our API.

    As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
    You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).

    ## HTTP response codes

        This API uses standard HTTP status codes. The ones you'll encounter:

        | Code | Meaning | When |
        |------|---------|------|
        | 200 | OK | Successful GET, PUT/PATCH, or a delete (returns a message body) |
        | 201 | Created | A resource was created (register, store) — returns the created resource |
        | 401 | Unauthorized | Missing or invalid access token |
        | 403 | Forbidden | Authenticated but not permitted (role, scope, or ownership policy fails) |
        | 404 | Not Found | The requested resource does not exist |
        | 409 | Conflict | State conflict (e.g. updating or closing an already-closed evaluation, or an incomplete evaluation) |
        | 422 | Unprocessable Entity | Validation failed — check the response body for field errors |
        | 500 | Internal Server Error | Unhandled server error |

