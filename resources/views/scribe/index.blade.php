<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Backend-Lyrium API</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.8.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.8.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-login">
                                <a href="#endpoints-POSTapi-auth-login">POST /api/auth/login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-register">
                                <a href="#endpoints-POSTapi-auth-register">POST /api/auth/register</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-register-customer">
                                <a href="#endpoints-POSTapi-auth-register-customer">POST /api/auth/register-customer</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-verify-otp">
                                <a href="#endpoints-POSTapi-auth-verify-otp">POST /api/auth/verify-otp</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-resend-otp">
                                <a href="#endpoints-POSTapi-auth-resend-otp">POST /api/auth/resend-otp</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-google-redirect">
                                <a href="#endpoints-GETapi-auth-google-redirect">GET /api/auth/google/redirect
Returns the Google OAuth redirect URL.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-google-callback">
                                <a href="#endpoints-GETapi-auth-google-callback">GET /api/auth/google/callback
Handles the Google OAuth callback and returns the same format as login.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-google">
                                <a href="#endpoints-POSTapi-auth-google">POST /api/auth/google
Legacy endpoint: verifies Google ID token directly (for mobile/web clients
that receive the token from Google's SDK on the frontend).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-social--provider-">
                                <a href="#endpoints-GETapi-auth-social--provider-">GET /api/auth/social/{provider}
Returns the OAuth redirect URL.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-social--provider--callback">
                                <a href="#endpoints-GETapi-auth-social--provider--callback">GET /api/auth/social/{provider}/callback
Handles the OAuth callback from the provider.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-logout">
                                <a href="#endpoints-POSTapi-auth-logout">POST /api/auth/logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-auth-validate">
                                <a href="#endpoints-GETapi-auth-validate">GET /api/auth/validate</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-auth-refresh">
                                <a href="#endpoints-POSTapi-auth-refresh">POST /api/auth/refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-home-banners-pub">
                                <a href="#endpoints-GETapi-home-banners-pub">GET api/home/banners-pub</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-home-heroes">
                                <a href="#endpoints-GETapi-home-heroes">GET api/home/heroes</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-brands">
                                <a href="#endpoints-GETapi-brands">GET api/brands</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories">
                                <a href="#endpoints-GETapi-categories">GET /api/categories</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories-services">
                                <a href="#endpoints-GETapi-categories-services">GET /api/categories/services</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-categories--id-">
                                <a href="#endpoints-GETapi-categories--id-">GET /api/categories/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-benefits">
                                <a href="#endpoints-GETapi-benefits">GET api/benefits</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-newsletter">
                                <a href="#endpoints-POSTapi-newsletter">POST api/newsletter</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-products">
                                <a href="#endpoints-GETapi-products">GET /api/products</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-products--id-">
                                <a href="#endpoints-GETapi-products--id-">GET /api/products/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-search">
                                <a href="#endpoints-GETapi-search">GET /api/search
Unified search with optional ?type=all returning products + categories + total_hits.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-search-products">
                                <a href="#endpoints-GETapi-search-products">GET /api/search/products
Search products with filters. Falls back to database search if Scout is unavailable.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-search-suggestions">
                                <a href="#endpoints-GETapi-search-suggestions">GET /api/search/suggestions
Get search suggestions/autocomplete. Falls back to database.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-users-me">
                                <a href="#endpoints-GETapi-users-me">GET /api/users/me</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-users--id-">
                                <a href="#endpoints-GETapi-users--id-">GET /api/users/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-users--id-">
                                <a href="#endpoints-PUTapi-users--id-">PUT /api/users/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-cart">
                                <a href="#endpoints-GETapi-cart">GET api/cart</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-cart-items">
                                <a href="#endpoints-POSTapi-cart-items">POST api/cart/items</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-cart-items--product_id-">
                                <a href="#endpoints-PATCHapi-cart-items--product_id-">PATCH api/cart/items/{product_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-cart-items--product_id-">
                                <a href="#endpoints-DELETEapi-cart-items--product_id-">DELETE api/cart/items/{product_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-cart">
                                <a href="#endpoints-DELETEapi-cart">DELETE api/cart</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-orders">
                                <a href="#endpoints-GETapi-orders">GET api/orders</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-orders--id-">
                                <a href="#endpoints-GETapi-orders--id-">GET api/orders/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-orders">
                                <a href="#endpoints-POSTapi-orders">POST api/orders</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-orders--id--status">
                                <a href="#endpoints-PUTapi-orders--id--status">PUT api/orders/{id}/status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-invoices">
                                <a href="#endpoints-GETapi-invoices">GET api/invoices</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-invoices--id-">
                                <a href="#endpoints-GETapi-invoices--id-">GET api/invoices/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-orders--orderId--invoice">
                                <a href="#endpoints-POSTapi-orders--orderId--invoice">POST api/orders/{orderId}/invoice</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-users">
                                <a href="#endpoints-GETapi-users">GET /api/users</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-users-role--role-">
                                <a href="#endpoints-GETapi-users-role--role-">GET /api/users/role/{role}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-users--id-">
                                <a href="#endpoints-DELETEapi-users--id-">DELETE /api/users/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-stores">
                                <a href="#endpoints-GETapi-stores">GET /api/stores</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-stores--id-">
                                <a href="#endpoints-GETapi-stores--id-">GET /api/stores/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-stores--id--status">
                                <a href="#endpoints-PUTapi-stores--id--status">PUT /api/stores/{id}/status
Admin: aprobar, rechazar o banear vendedores</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-categories">
                                <a href="#endpoints-POSTapi-categories">POST /api/categories</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-categories--id-">
                                <a href="#endpoints-PUTapi-categories--id-">PUT /api/categories/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-categories--id-">
                                <a href="#endpoints-DELETEapi-categories--id-">DELETE /api/categories/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-products--id--status">
                                <a href="#endpoints-PUTapi-products--id--status">PUT /api/products/{id}/status
Admin: aprobar o rechazar productos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-seller-profile">
                                <a href="#endpoints-GETapi-seller-profile">GET /api/seller/profile
Get current seller's profile.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-seller-profile">
                                <a href="#endpoints-PUTapi-seller-profile">PUT /api/seller/profile
Update current seller's profile.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-seller-store">
                                <a href="#endpoints-GETapi-seller-store">GET /api/seller/store
Get current seller's store data.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-stores">
                                <a href="#endpoints-POSTapi-stores">POST /api/stores</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-stores--id-">
                                <a href="#endpoints-PUTapi-stores--id-">PUT /api/stores/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-stores--id--media-logo">
                                <a href="#endpoints-POSTapi-stores--id--media-logo">Upload store logo.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-stores--id--media-banner">
                                <a href="#endpoints-POSTapi-stores--id--media-banner">Upload store banner.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-stores--id--media--mediaId-">
                                <a href="#endpoints-DELETEapi-stores--id--media--mediaId-">Delete store media.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-products">
                                <a href="#endpoints-POSTapi-products">POST /api/products</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-products--id-">
                                <a href="#endpoints-PUTapi-products--id-">PUT /api/products/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-products--id-">
                                <a href="#endpoints-DELETEapi-products--id-">DELETE /api/products/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-products--id--stock">
                                <a href="#endpoints-PUTapi-products--id--stock">PUT /api/products/{id}/stock</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-products--id--media">
                                <a href="#endpoints-GETapi-products--id--media">Get product media.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-products--id--media">
                                <a href="#endpoints-POSTapi-products--id--media">Upload media to a product.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-products--id--media--mediaId-">
                                <a href="#endpoints-DELETEapi-products--id--media--mediaId-">Delete product media.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-products--id--media-reorder">
                                <a href="#endpoints-PUTapi-products--id--media-reorder">Reorder product media.</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: March 20, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Multi-vendor biomarketplace REST API built with Laravel 12. Provides endpoints for authentication, product management, categories, stores, media uploads, and search functionality.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<p>Backend-Lyrium es una API RESTful para un biomercado multi-vendedor.</p>
<p><strong>Autenticación:</strong> Bearer token via Laravel Sanctum</p>
<p><strong>Headers requeridos:</strong></p>
<pre>Accept: application/json
Authorization: Bearer {token}</pre>
<aside>Documentación generada automáticamente con Scribe.</aside>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-POSTapi-auth-login">POST /api/auth/login</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\",
    \"password\": \"O[2UZ5ij-e\\/dl4m{o,\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com",
    "password": "O[2UZ5ij-e\/dl4m{o,"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-login">
</span>
<span id="execution-results-POSTapi-auth-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-login" data-method="POST"
      data-path="api/auth/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-login"
                    onclick="tryItOut('POSTapi-auth-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-login"
                    onclick="cancelTryOut('POSTapi-auth-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-login"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>qkunze@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-auth-login"
               value="O[2UZ5ij-e/dl4m{o,"
               data-component="body">
    <br>
<p>Example: <code>O[2UZ5ij-e/dl4m{o,</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-register">POST /api/auth/register</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/register" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"storeName\": \"vmqeopfuudtdsufvyvddq\",
    \"email\": \"kunde.eloisa@example.com\",
    \"phone\": \"hfqcoynlazghdtqtq\",
    \"password\": \"(!Cs\'YAKYLk4&gt;SJIrIV\",
    \"ruc\": \"auydlsmsjur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/register"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "storeName": "vmqeopfuudtdsufvyvddq",
    "email": "kunde.eloisa@example.com",
    "phone": "hfqcoynlazghdtqtq",
    "password": "(!Cs'YAKYLk4&gt;SJIrIV",
    "ruc": "auydlsmsjur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-register">
</span>
<span id="execution-results-POSTapi-auth-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-register" data-method="POST"
      data-path="api/auth/register"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-register"
                    onclick="tryItOut('POSTapi-auth-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-register"
                    onclick="cancelTryOut('POSTapi-auth-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>storeName</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="storeName"                data-endpoint="POSTapi-auth-register"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-register"
               value="kunde.eloisa@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>kunde.eloisa@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-auth-register"
               value="hfqcoynlazghdtqtq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>hfqcoynlazghdtqtq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-auth-register"
               value="(!Cs'YAKYLk4>SJIrIV"
               data-component="body">
    <br>
<p>validation.min. Example: <code>(!Cs'YAKYLk4&gt;SJIrIV</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ruc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ruc"                data-endpoint="POSTapi-auth-register"
               value="auydlsmsjur"
               data-component="body">
    <br>
<p>validation.size. Example: <code>auydlsmsjur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-register-customer">POST /api/auth/register-customer</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-register-customer">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/register-customer" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"email\": \"kunde.eloisa@example.com\",
    \"password\": \"4[*UyPJ\\\"}6\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/register-customer"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "email": "kunde.eloisa@example.com",
    "password": "4[*UyPJ\"}6"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-register-customer">
</span>
<span id="execution-results-POSTapi-auth-register-customer" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-register-customer"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-register-customer"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-register-customer" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-register-customer">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-register-customer" data-method="POST"
      data-path="api/auth/register-customer"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-register-customer', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-register-customer"
                    onclick="tryItOut('POSTapi-auth-register-customer');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-register-customer"
                    onclick="cancelTryOut('POSTapi-auth-register-customer');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-register-customer"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/register-customer</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-register-customer"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-register-customer"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-auth-register-customer"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-register-customer"
               value="kunde.eloisa@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>kunde.eloisa@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-auth-register-customer"
               value="4[*UyPJ"}6"
               data-component="body">
    <br>
<p>validation.min. Example: <code>4[*UyPJ"}6</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-verify-otp">POST /api/auth/verify-otp</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-verify-otp">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/verify-otp" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\",
    \"code\": \"opfuud\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/verify-otp"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com",
    "code": "opfuud"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-verify-otp">
</span>
<span id="execution-results-POSTapi-auth-verify-otp" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-verify-otp"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-verify-otp"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-verify-otp" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-verify-otp">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-verify-otp" data-method="POST"
      data-path="api/auth/verify-otp"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-verify-otp', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-verify-otp"
                    onclick="tryItOut('POSTapi-auth-verify-otp');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-verify-otp"
                    onclick="cancelTryOut('POSTapi-auth-verify-otp');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-verify-otp"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/verify-otp</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-verify-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-verify-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-verify-otp"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>qkunze@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="POSTapi-auth-verify-otp"
               value="opfuud"
               data-component="body">
    <br>
<p>validation.size. Example: <code>opfuud</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-auth-resend-otp">POST /api/auth/resend-otp</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-resend-otp">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/resend-otp" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"qkunze@example.com\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/resend-otp"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "qkunze@example.com"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-resend-otp">
</span>
<span id="execution-results-POSTapi-auth-resend-otp" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-resend-otp"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-resend-otp"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-resend-otp" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-resend-otp">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-resend-otp" data-method="POST"
      data-path="api/auth/resend-otp"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-resend-otp', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-resend-otp"
                    onclick="tryItOut('POSTapi-auth-resend-otp');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-resend-otp"
                    onclick="cancelTryOut('POSTapi-auth-resend-otp');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-resend-otp"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/resend-otp</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-resend-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-resend-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-auth-resend-otp"
               value="qkunze@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>qkunze@example.com</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-auth-google-redirect">GET /api/auth/google/redirect
Returns the Google OAuth redirect URL.</h2>

<p>
</p>



<span id="example-requests-GETapi-auth-google-redirect">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/auth/google/redirect" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/google/redirect"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-google-redirect">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;redirect_url&quot;: &quot;https://accounts.google.com/o/oauth2/auth?client_id=363073868682-tvls3e7t1lmr101js5sah0phn5aueeja.apps.googleusercontent.com&amp;redirect_uri=http%3A%2F%2Flocalhost%3A8000%2Fapi%2Fauth%2Fsocial%2Fgoogle%2Fcallback&amp;scope=openid+profile+email&amp;response_type=code&amp;state=Y5t0LZirrBFXI7kUwUBjg35KiW6P8aYWqscmsaNM&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-google-redirect" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-google-redirect"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-google-redirect"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-google-redirect" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-google-redirect">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-google-redirect" data-method="GET"
      data-path="api/auth/google/redirect"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-google-redirect', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-google-redirect"
                    onclick="tryItOut('GETapi-auth-google-redirect');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-google-redirect"
                    onclick="cancelTryOut('GETapi-auth-google-redirect');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-google-redirect"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/google/redirect</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-google-redirect"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-google-redirect"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-auth-google-callback">GET /api/auth/google/callback
Handles the Google OAuth callback and returns the same format as login.</h2>

<p>
</p>



<span id="example-requests-GETapi-auth-google-callback">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/auth/google/callback" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/google/callback"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-google-callback">
            <blockquote>
            <p>Example response (400):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: &quot;C&oacute;digo o estado faltante.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-google-callback" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-google-callback"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-google-callback"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-google-callback" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-google-callback">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-google-callback" data-method="GET"
      data-path="api/auth/google/callback"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-google-callback', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-google-callback"
                    onclick="tryItOut('GETapi-auth-google-callback');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-google-callback"
                    onclick="cancelTryOut('GETapi-auth-google-callback');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-google-callback"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/google/callback</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-google-callback"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-google-callback"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-google">POST /api/auth/google
Legacy endpoint: verifies Google ID token directly (for mobile/web clients
that receive the token from Google&#039;s SDK on the frontend).</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-google">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/google" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"credential\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/google"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "credential": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-google">
</span>
<span id="execution-results-POSTapi-auth-google" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-google"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-google"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-google" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-google">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-google" data-method="POST"
      data-path="api/auth/google"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-google', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-google"
                    onclick="tryItOut('POSTapi-auth-google');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-google"
                    onclick="cancelTryOut('POSTapi-auth-google');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-google"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/google</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-google"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-google"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>credential</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="credential"                data-endpoint="POSTapi-auth-google"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-auth-social--provider-">GET /api/auth/social/{provider}
Returns the OAuth redirect URL.</h2>

<p>
</p>

<p>Optional query param: ?frontend_url=<a href="https://yourfrontend.com">https://yourfrontend.com</a>
If provided, the state will redirect to that URL after OAuth.</p>

<span id="example-requests-GETapi-auth-social--provider-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/auth/social/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/social/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-social--provider-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_PROVIDER&quot;,
        &quot;message&quot;: &quot;Provider no soportado&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-social--provider-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-social--provider-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-social--provider-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-social--provider-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-social--provider-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-social--provider-" data-method="GET"
      data-path="api/auth/social/{provider}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-social--provider-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-social--provider-"
                    onclick="tryItOut('GETapi-auth-social--provider-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-social--provider-"
                    onclick="cancelTryOut('GETapi-auth-social--provider-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-social--provider-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/social/{provider}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-social--provider-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-social--provider-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="provider"                data-endpoint="GETapi-auth-social--provider-"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-auth-social--provider--callback">GET /api/auth/social/{provider}/callback
Handles the OAuth callback from the provider.</h2>

<p>
</p>

<p>Returns JSON with token + user (for frontend API route).</p>

<span id="example-requests-GETapi-auth-social--provider--callback">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/auth/social/consequatur/callback" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/social/consequatur/callback"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-social--provider--callback">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;error&quot;: {
        &quot;code&quot;: &quot;INVALID_PROVIDER&quot;,
        &quot;message&quot;: &quot;Provider no soportado&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-social--provider--callback" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-social--provider--callback"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-social--provider--callback"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-social--provider--callback" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-social--provider--callback">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-social--provider--callback" data-method="GET"
      data-path="api/auth/social/{provider}/callback"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-social--provider--callback', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-social--provider--callback"
                    onclick="tryItOut('GETapi-auth-social--provider--callback');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-social--provider--callback"
                    onclick="cancelTryOut('GETapi-auth-social--provider--callback');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-social--provider--callback"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/social/{provider}/callback</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-social--provider--callback"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-social--provider--callback"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="provider"                data-endpoint="GETapi-auth-social--provider--callback"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-auth-logout">POST /api/auth/logout</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-logout">
</span>
<span id="execution-results-POSTapi-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-logout" data-method="POST"
      data-path="api/auth/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-logout"
                    onclick="tryItOut('POSTapi-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-logout"
                    onclick="cancelTryOut('POSTapi-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-auth-validate">GET /api/auth/validate</h2>

<p>
</p>



<span id="example-requests-GETapi-auth-validate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/auth/validate" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/validate"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-auth-validate">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-auth-validate" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-auth-validate"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-auth-validate"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-auth-validate" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-auth-validate">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-auth-validate" data-method="GET"
      data-path="api/auth/validate"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-auth-validate', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-auth-validate"
                    onclick="tryItOut('GETapi-auth-validate');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-auth-validate"
                    onclick="cancelTryOut('GETapi-auth-validate');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-auth-validate"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/auth/validate</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-auth-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-auth-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-auth-refresh">POST /api/auth/refresh</h2>

<p>
</p>



<span id="example-requests-POSTapi-auth-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/auth/refresh" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/auth/refresh"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-auth-refresh">
</span>
<span id="execution-results-POSTapi-auth-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-auth-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-auth-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-auth-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-auth-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-auth-refresh" data-method="POST"
      data-path="api/auth/refresh"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-auth-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-auth-refresh"
                    onclick="tryItOut('POSTapi-auth-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-auth-refresh"
                    onclick="cancelTryOut('POSTapi-auth-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-auth-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/auth/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-auth-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-auth-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-home-banners-pub">GET api/home/banners-pub</h2>

<p>
</p>



<span id="example-requests-GETapi-home-banners-pub">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/home/banners-pub" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/home/banners-pub"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-home-banners-pub">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;slider1&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 1&quot;,
                &quot;descripcion&quot;: &quot;Promoci&oacute;n especial de temporada&quot;,
                &quot;imagenes&quot;: [
                    &quot;/img/Inicio/1.png&quot;,
                    &quot;/img/Inicio/movil/1.webp&quot;
                ]
            },
            {
                &quot;id&quot;: 2,
                &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 2&quot;,
                &quot;descripcion&quot;: &quot;Descubre nuestras ofertas exclusivas&quot;,
                &quot;imagenes&quot;: [
                    &quot;/img/Inicio/2.png&quot;,
                    &quot;/img/Inicio/movil/2.webp&quot;
                ]
            },
            {
                &quot;id&quot;: 3,
                &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 3&quot;,
                &quot;descripcion&quot;: &quot;Novedades para tu bienestar&quot;,
                &quot;imagenes&quot;: [
                    &quot;/img/Inicio/3.png&quot;,
                    &quot;/img/Inicio/movil/3.webp&quot;
                ]
            },
            {
                &quot;id&quot;: 4,
                &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 4&quot;,
                &quot;descripcion&quot;: &quot;Ofertas por tiempo limitado&quot;,
                &quot;imagenes&quot;: [
                    &quot;/img/Inicio/4.png&quot;,
                    &quot;/img/Inicio/movil/4.webp&quot;
                ]
            },
            {
                &quot;id&quot;: 5,
                &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 5&quot;,
                &quot;descripcion&quot;: &quot;Marcas aliadas&quot;,
                &quot;imagenes&quot;: [
                    &quot;/img/Inicio/5.png&quot;,
                    &quot;/img/Inicio/movil/5.webp&quot;
                ]
            },
            {
                &quot;id&quot;: 6,
                &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 6&quot;,
                &quot;descripcion&quot;: &quot;Compra f&aacute;cil y segura&quot;,
                &quot;imagenes&quot;: [
                    &quot;/img/Inicio/6.png&quot;,
                    &quot;/img/Inicio/movil/6.webp&quot;
                ]
            }
        ],
        &quot;pequenos1&quot;: [
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.1.webp&quot;,
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.2.webp&quot;,
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.3.webp&quot;,
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_2.4.webp&quot;
        ],
        &quot;slider2&quot;: [
            {
                &quot;id&quot;: 11,
                &quot;titulo&quot;: &quot;Banner Mediano 1&quot;,
                &quot;descripcion&quot;: null,
                &quot;imagenes&quot;: [
                    &quot;/img/banners_publicitarios/banner_mediano/banner_mediano_3.1.webp&quot;,
                    &quot;/img/banners_publicitarios/banner_mediano/banner_mediano_3.2.webp&quot;
                ]
            },
            {
                &quot;id&quot;: 12,
                &quot;titulo&quot;: &quot;Banner Mediano 2&quot;,
                &quot;descripcion&quot;: null,
                &quot;imagenes&quot;: [
                    &quot;/img/banners_publicitarios/banner_mediano/banner_mediano_3.3.webp&quot;,
                    &quot;/img/banners_publicitarios/banner_mediano/banner_mediano_3.1.webp&quot;
                ]
            }
        ],
        &quot;pequenos2&quot;: [
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.1.webp&quot;,
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.2.webp&quot;,
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.3.webp&quot;,
            &quot;/img/banners_publicitarios/banner_pequeno/banner_pequeno_4.4.webp&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-home-banners-pub" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-home-banners-pub"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-home-banners-pub"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-home-banners-pub" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-home-banners-pub">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-home-banners-pub" data-method="GET"
      data-path="api/home/banners-pub"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-home-banners-pub', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-home-banners-pub"
                    onclick="tryItOut('GETapi-home-banners-pub');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-home-banners-pub"
                    onclick="cancelTryOut('GETapi-home-banners-pub');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-home-banners-pub"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/home/banners-pub</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-home-banners-pub"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-home-banners-pub"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-home-heroes">GET api/home/heroes</h2>

<p>
</p>



<span id="example-requests-GETapi-home-heroes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/home/heroes" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/home/heroes"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-home-heroes">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 1&quot;,
            &quot;descripcion&quot;: &quot;Promoci&oacute;n especial de temporada&quot;,
            &quot;images&quot;: {
                &quot;desktop&quot;: &quot;/img/Inicio/1.png&quot;,
                &quot;mobile&quot;: &quot;/img/Inicio/movil/1.webp&quot;
            }
        },
        {
            &quot;id&quot;: 2,
            &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 2&quot;,
            &quot;descripcion&quot;: &quot;Descubre nuestras ofertas exclusivas&quot;,
            &quot;images&quot;: {
                &quot;desktop&quot;: &quot;/img/Inicio/2.png&quot;,
                &quot;mobile&quot;: &quot;/img/Inicio/movil/2.webp&quot;
            }
        },
        {
            &quot;id&quot;: 3,
            &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 3&quot;,
            &quot;descripcion&quot;: &quot;Novedades para tu bienestar&quot;,
            &quot;images&quot;: {
                &quot;desktop&quot;: &quot;/img/Inicio/3.png&quot;,
                &quot;mobile&quot;: &quot;/img/Inicio/movil/3.webp&quot;
            }
        },
        {
            &quot;id&quot;: 4,
            &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 4&quot;,
            &quot;descripcion&quot;: &quot;Ofertas por tiempo limitado&quot;,
            &quot;images&quot;: {
                &quot;desktop&quot;: &quot;/img/Inicio/4.png&quot;,
                &quot;mobile&quot;: &quot;/img/Inicio/movil/4.webp&quot;
            }
        },
        {
            &quot;id&quot;: 5,
            &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 5&quot;,
            &quot;descripcion&quot;: &quot;Marcas aliadas&quot;,
            &quot;images&quot;: {
                &quot;desktop&quot;: &quot;/img/Inicio/5.png&quot;,
                &quot;mobile&quot;: &quot;/img/Inicio/movil/5.webp&quot;
            }
        },
        {
            &quot;id&quot;: 6,
            &quot;titulo&quot;: &quot;Campa&ntilde;a Especial 6&quot;,
            &quot;descripcion&quot;: &quot;Compra f&aacute;cil y segura&quot;,
            &quot;images&quot;: {
                &quot;desktop&quot;: &quot;/img/Inicio/6.png&quot;,
                &quot;mobile&quot;: &quot;/img/Inicio/movil/6.webp&quot;
            }
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-home-heroes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-home-heroes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-home-heroes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-home-heroes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-home-heroes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-home-heroes" data-method="GET"
      data-path="api/home/heroes"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-home-heroes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-home-heroes"
                    onclick="tryItOut('GETapi-home-heroes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-home-heroes"
                    onclick="cancelTryOut('GETapi-home-heroes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-home-heroes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/home/heroes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-home-heroes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-home-heroes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-brands">GET api/brands</h2>

<p>
</p>



<span id="example-requests-GETapi-brands">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/brands" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/brands"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-brands">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;nombre&quot;: &quot;BioNature&quot;,
            &quot;logo&quot;: &quot;/img/Inicio/3/1.png&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;nombre&quot;: &quot;EcoVida&quot;,
            &quot;logo&quot;: &quot;/img/Inicio/3/2.png&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;nombre&quot;: &quot;GreenLife&quot;,
            &quot;logo&quot;: &quot;/img/Inicio/3/3.png&quot;
        },
        {
            &quot;id&quot;: 4,
            &quot;nombre&quot;: &quot;PureWell&quot;,
            &quot;logo&quot;: &quot;/img/Inicio/3/4.png&quot;
        },
        {
            &quot;id&quot;: 5,
            &quot;nombre&quot;: &quot;NaturaPlus&quot;,
            &quot;logo&quot;: &quot;/img/Inicio/3/5.png&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;nombre&quot;: &quot;VitaOrganica&quot;,
            &quot;logo&quot;: &quot;/img/Inicio/3/6.png&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-brands" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-brands"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-brands"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-brands" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-brands">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-brands" data-method="GET"
      data-path="api/brands"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-brands', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-brands"
                    onclick="tryItOut('GETapi-brands');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-brands"
                    onclick="cancelTryOut('GETapi-brands');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-brands"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/brands</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-brands"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-brands"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-categories">GET /api/categories</h2>

<p>
</p>



<span id="example-requests-GETapi-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/categories" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/categories"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Semillas&quot;,
            &quot;slug&quot;: &quot;semillas&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 2,
            &quot;image&quot;: {
                &quot;id&quot;: 1,
                &quot;src&quot;: &quot;/img/Inicio/2/1.png&quot;,
                &quot;name&quot;: &quot;Semillas&quot;
            }
        },
        {
            &quot;id&quot;: 2,
            &quot;name&quot;: &quot;Fertilizantes&quot;,
            &quot;slug&quot;: &quot;fertilizantes&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 3,
            &quot;image&quot;: {
                &quot;id&quot;: 2,
                &quot;src&quot;: &quot;/img/Inicio/2/2.png&quot;,
                &quot;name&quot;: &quot;Fertilizantes&quot;
            }
        },
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Herramientas&quot;,
            &quot;slug&quot;: &quot;herramientas&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 3,
            &quot;image&quot;: {
                &quot;id&quot;: 3,
                &quot;src&quot;: &quot;/img/Inicio/2/3.png&quot;,
                &quot;name&quot;: &quot;Herramientas&quot;
            }
        },
        {
            &quot;id&quot;: 4,
            &quot;name&quot;: &quot;Suplementos&quot;,
            &quot;slug&quot;: &quot;suplementos&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 4,
            &quot;image&quot;: {
                &quot;id&quot;: 4,
                &quot;src&quot;: &quot;/img/Inicio/2/4.png&quot;,
                &quot;name&quot;: &quot;Suplementos&quot;
            }
        },
        {
            &quot;id&quot;: 5,
            &quot;name&quot;: &quot;Alimentos Org&aacute;nicos&quot;,
            &quot;slug&quot;: &quot;alimentos-organicos&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 6,
            &quot;image&quot;: {
                &quot;id&quot;: 5,
                &quot;src&quot;: &quot;/img/Inicio/2/5.png&quot;,
                &quot;name&quot;: &quot;Alimentos Org&aacute;nicos&quot;
            }
        },
        {
            &quot;id&quot;: 6,
            &quot;name&quot;: &quot;Cuidado Personal&quot;,
            &quot;slug&quot;: &quot;cuidado-personal&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 5,
            &quot;image&quot;: {
                &quot;id&quot;: 6,
                &quot;src&quot;: &quot;/img/Inicio/2/6.png&quot;,
                &quot;name&quot;: &quot;Cuidado Personal&quot;
            }
        },
        {
            &quot;id&quot;: 7,
            &quot;name&quot;: &quot;Aceites Esenciales&quot;,
            &quot;slug&quot;: &quot;aceites-esenciales&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 3,
            &quot;image&quot;: null
        },
        {
            &quot;id&quot;: 8,
            &quot;name&quot;: &quot;Productos Naturales&quot;,
            &quot;slug&quot;: &quot;productos-naturales&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 7,
            &quot;image&quot;: null
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories" data-method="GET"
      data-path="api/categories"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories"
                    onclick="tryItOut('GETapi-categories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories"
                    onclick="cancelTryOut('GETapi-categories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-categories-services">GET /api/categories/services</h2>

<p>
</p>



<span id="example-requests-GETapi-categories-services">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/categories/services" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/categories/services"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories-services">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 9,
            &quot;name&quot;: &quot;Servicios de Salud&quot;,
            &quot;slug&quot;: &quot;servicios-salud&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 0,
            &quot;image&quot;: {
                &quot;id&quot;: 9,
                &quot;src&quot;: &quot;/img/Inicio/1/1.png&quot;,
                &quot;name&quot;: &quot;Servicios de Salud&quot;
            }
        },
        {
            &quot;id&quot;: 10,
            &quot;name&quot;: &quot;Medicina Natural&quot;,
            &quot;slug&quot;: &quot;medicina-natural&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 0,
            &quot;image&quot;: {
                &quot;id&quot;: 10,
                &quot;src&quot;: &quot;/img/Inicio/1/2.png&quot;,
                &quot;name&quot;: &quot;Medicina Natural&quot;
            }
        },
        {
            &quot;id&quot;: 11,
            &quot;name&quot;: &quot;Belleza y Cuidado&quot;,
            &quot;slug&quot;: &quot;belleza-cuidado&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 0,
            &quot;image&quot;: {
                &quot;id&quot;: 11,
                &quot;src&quot;: &quot;/img/Inicio/1/3.png&quot;,
                &quot;name&quot;: &quot;Belleza y Cuidado&quot;
            }
        },
        {
            &quot;id&quot;: 12,
            &quot;name&quot;: &quot;Terapias Alternativas&quot;,
            &quot;slug&quot;: &quot;terapias-alternativas&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 0,
            &quot;image&quot;: {
                &quot;id&quot;: 12,
                &quot;src&quot;: &quot;/img/Inicio/1/4.png&quot;,
                &quot;name&quot;: &quot;Terapias Alternativas&quot;
            }
        },
        {
            &quot;id&quot;: 13,
            &quot;name&quot;: &quot;Nutrici&oacute;n&quot;,
            &quot;slug&quot;: &quot;nutricion&quot;,
            &quot;parent&quot;: 0,
            &quot;description&quot;: &quot;&quot;,
            &quot;count&quot;: 0,
            &quot;image&quot;: {
                &quot;id&quot;: 13,
                &quot;src&quot;: &quot;/img/Inicio/1/1.png&quot;,
                &quot;name&quot;: &quot;Nutrici&oacute;n&quot;
            }
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories-services" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories-services"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories-services"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories-services" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories-services">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories-services" data-method="GET"
      data-path="api/categories/services"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories-services', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories-services"
                    onclick="tryItOut('GETapi-categories-services');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories-services"
                    onclick="cancelTryOut('GETapi-categories-services');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories-services"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories/services</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories-services"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories-services"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-categories--id-">GET /api/categories/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/categories/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/categories/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-categories--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Semillas&quot;,
        &quot;slug&quot;: &quot;semillas&quot;,
        &quot;parent&quot;: 0,
        &quot;description&quot;: &quot;&quot;,
        &quot;count&quot;: 2,
        &quot;image&quot;: {
            &quot;id&quot;: 1,
            &quot;src&quot;: &quot;/img/Inicio/2/1.png&quot;,
            &quot;name&quot;: &quot;Semillas&quot;
        },
        &quot;children&quot;: []
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-categories--id-" data-method="GET"
      data-path="api/categories/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-categories--id-"
                    onclick="tryItOut('GETapi-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-categories--id-"
                    onclick="cancelTryOut('GETapi-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-categories--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-benefits">GET api/benefits</h2>

<p>
</p>



<span id="example-requests-GETapi-benefits">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/benefits" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/benefits"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-benefits">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;titulo&quot;: &quot;Todo Salud&quot;,
            &quot;descripcion&quot;: &quot;Tiendas saludables y ecoamigables para tu bienestar&quot;,
            &quot;icono&quot;: &quot;heart&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;titulo&quot;: &quot;Tiendas Selectas&quot;,
            &quot;descripcion&quot;: &quot;Tiendas de calidad cuidadosamente seleccionadas para ti&quot;,
            &quot;icono&quot;: &quot;storefront&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;titulo&quot;: &quot;Mejores Precios&quot;,
            &quot;descripcion&quot;: &quot;Mejores ofertas, promociones y descuentos&quot;,
            &quot;icono&quot;: &quot;tag&quot;
        },
        {
            &quot;id&quot;: 4,
            &quot;titulo&quot;: &quot;Seguridad&quot;,
            &quot;descripcion&quot;: &quot;Biomarketplace 100% seguro&quot;,
            &quot;icono&quot;: &quot;shield-check&quot;
        },
        {
            &quot;id&quot;: 5,
            &quot;titulo&quot;: &quot;Rapidez&quot;,
            &quot;descripcion&quot;: &quot;Mayor rapidez en tus compras&quot;,
            &quot;icono&quot;: &quot;lightning&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;titulo&quot;: &quot;M&aacute;s Tiempo&quot;,
            &quot;descripcion&quot;: &quot;Ahorra tiempo en transportarte y en colas presenciales&quot;,
            &quot;icono&quot;: &quot;clock&quot;
        },
        {
            &quot;id&quot;: 7,
            &quot;titulo&quot;: &quot;Donde Quieras&quot;,
            &quot;descripcion&quot;: &quot;Env&iacute;os a todo el Per&uacute;&quot;,
            &quot;icono&quot;: &quot;globe&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-benefits" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-benefits"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-benefits"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-benefits" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-benefits">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-benefits" data-method="GET"
      data-path="api/benefits"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-benefits', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-benefits"
                    onclick="tryItOut('GETapi-benefits');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-benefits"
                    onclick="cancelTryOut('GETapi-benefits');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-benefits"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/benefits</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-benefits"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-benefits"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-newsletter">POST api/newsletter</h2>

<p>
</p>



<span id="example-requests-POSTapi-newsletter">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/newsletter" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/newsletter"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-newsletter">
</span>
<span id="execution-results-POSTapi-newsletter" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-newsletter"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-newsletter"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-newsletter" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-newsletter">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-newsletter" data-method="POST"
      data-path="api/newsletter"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-newsletter', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-newsletter"
                    onclick="tryItOut('POSTapi-newsletter');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-newsletter"
                    onclick="cancelTryOut('POSTapi-newsletter');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-newsletter"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/newsletter</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-newsletter"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-newsletter"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-products">GET /api/products</h2>

<p>
</p>



<span id="example-requests-GETapi-products">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/products" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-products">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;data&quot;: [
            {
                &quot;id&quot;: &quot;3&quot;,
                &quot;name&quot;: &quot;Aceite de Coco Virgen Extra&quot;,
                &quot;slug&quot;: &quot;aceite-coco-virgen-extra&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-001&quot;,
                &quot;category&quot;: &quot;alimentos-organicos&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Alimentos Org&aacute;nicos&quot;,
                        &quot;slug&quot;: &quot;alimentos-organicos&quot;
                    },
                    {
                        &quot;id&quot;: 7,
                        &quot;name&quot;: &quot;Aceites Esenciales&quot;,
                        &quot;slug&quot;: &quot;aceites-esenciales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% puro y org&aacute;nico. Perfecto para cocinar, cosm&eacute;tica natural y cuidado del cabello.&quot;,
                &quot;short_description&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% org&aacute;nico&quot;,
                &quot;shortDescription&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% org&aacute;nico&quot;,
                &quot;price&quot;: 32,
                &quot;regular_price&quot;: &quot;38.00&quot;,
                &quot;regularPrice&quot;: 38,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 80,
                &quot;stock&quot;: 80,
                &quot;weight&quot;: 1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.79,
                &quot;ratingPromedio&quot;: 4.9,
                &quot;ratingCount&quot;: 89,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;4&quot;,
                &quot;name&quot;: &quot;Fertilizante Org&aacute;nico L&iacute;quido&quot;,
                &quot;slug&quot;: &quot;fertilizante-organico-liquido&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-001&quot;,
                &quot;category&quot;: &quot;fertilizantes&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 2,
                        &quot;name&quot;: &quot;Fertilizantes&quot;,
                        &quot;slug&quot;: &quot;fertilizantes&quot;
                    }
                ],
                &quot;description&quot;: &quot;Fertilizante l&iacute;quido 100% org&aacute;nico para plantas dom&eacute;sticas y huertos. A base de extracto de algas marinas y compost.&quot;,
                &quot;short_description&quot;: &quot;Fertilizante l&iacute;quido org&aacute;nico para plantas&quot;,
                &quot;shortDescription&quot;: &quot;Fertilizante l&iacute;quido org&aacute;nico para plantas&quot;,
                &quot;price&quot;: 18.5,
                &quot;regular_price&quot;: &quot;22.00&quot;,
                &quot;regularPrice&quot;: 22,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 200,
                &quot;stock&quot;: 200,
                &quot;weight&quot;: 1.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.91,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 67,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;5&quot;,
                &quot;name&quot;: &quot;T&eacute; Verde Matcha Ceremonial Premium&quot;,
                &quot;slug&quot;: &quot;te-verde-matcha-ceremonial&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-001&quot;,
                &quot;category&quot;: &quot;alimentos-organicos&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Alimentos Org&aacute;nicos&quot;,
                        &quot;slug&quot;: &quot;alimentos-organicos&quot;
                    },
                    {
                        &quot;id&quot;: 8,
                        &quot;name&quot;: &quot;Productos Naturales&quot;,
                        &quot;slug&quot;: &quot;productos-naturales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Matcha ceremonial de primera calidad, cultivado en sombra para m&aacute;xima concentraci&oacute;n de clorofila y antioxidantes.&quot;,
                &quot;short_description&quot;: &quot;Matcha ceremonial premium de Jap&oacute;n&quot;,
                &quot;shortDescription&quot;: &quot;Matcha ceremonial premium de Jap&oacute;n&quot;,
                &quot;price&quot;: 45,
                &quot;regular_price&quot;: &quot;55.00&quot;,
                &quot;regularPrice&quot;: 55,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 60,
                &quot;stock&quot;: 60,
                &quot;weight&quot;: 0.1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 18.18,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 203,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;6&quot;,
                &quot;name&quot;: &quot;Jab&oacute;n de Castilla Natural&quot;,
                &quot;slug&quot;: &quot;jabon-castilla-natural&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-001&quot;,
                &quot;category&quot;: &quot;cuidado-personal&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Cuidado Personal&quot;,
                        &quot;slug&quot;: &quot;cuidado-personal&quot;
                    },
                    {
                        &quot;id&quot;: 8,
                        &quot;name&quot;: &quot;Productos Naturales&quot;,
                        &quot;slug&quot;: &quot;productos-naturales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Jab&oacute;n artesanal de aceite de oliva, hipoalerg&eacute;nico y sin fragancias. Apto para pieles sensibles y uso diario.&quot;,
                &quot;short_description&quot;: &quot;Jab&oacute;n artesanal hipoalerg&eacute;nico&quot;,
                &quot;shortDescription&quot;: &quot;Jab&oacute;n artesanal hipoalerg&eacute;nico&quot;,
                &quot;price&quot;: 12,
                &quot;regular_price&quot;: &quot;14.00&quot;,
                &quot;regularPrice&quot;: 14,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 300,
                &quot;stock&quot;: 300,
                &quot;weight&quot;: 0.15,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.29,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 156,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;7&quot;,
                &quot;name&quot;: &quot;Kit de Semillas para Huerto Urbano&quot;,
                &quot;slug&quot;: &quot;kit-semillas-huerto-urbano&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-001&quot;,
                &quot;category&quot;: &quot;semillas&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 1,
                        &quot;name&quot;: &quot;Semillas&quot;,
                        &quot;slug&quot;: &quot;semillas&quot;
                    },
                    {
                        &quot;id&quot;: 3,
                        &quot;name&quot;: &quot;Herramientas&quot;,
                        &quot;slug&quot;: &quot;herramientas&quot;
                    }
                ],
                &quot;description&quot;: &quot;Kit con 10 variedades de semillas org&aacute;nicas para cultivar tu propio huerto en casa. Incluye tomate, lechuga, albahaca, pimiento y m&aacute;s.&quot;,
                &quot;short_description&quot;: &quot;10 variedades de semillas para huerto urbano&quot;,
                &quot;shortDescription&quot;: &quot;10 variedades de semillas para huerto urbano&quot;,
                &quot;price&quot;: 35,
                &quot;regular_price&quot;: &quot;35.00&quot;,
                &quot;regularPrice&quot;: 35,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 45,
                &quot;stock&quot;: 45,
                &quot;weight&quot;: 0.3,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 0,
                &quot;ratingPromedio&quot;: 4.4,
                &quot;ratingCount&quot;: 38,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;8&quot;,
                &quot;name&quot;: &quot;Probi&oacute;ticos Flora Intestinal&quot;,
                &quot;slug&quot;: &quot;probi&oacute;ticos-flora-intestinal&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SUP-001&quot;,
                &quot;category&quot;: &quot;suplementos&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 4,
                        &quot;name&quot;: &quot;Suplementos&quot;,
                        &quot;slug&quot;: &quot;suplementos&quot;
                    },
                    {
                        &quot;id&quot;: 8,
                        &quot;name&quot;: &quot;Productos Naturales&quot;,
                        &quot;slug&quot;: &quot;productos-naturales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Suplemento de probi&oacute;ticos con 50 mil millones de UFC. 10 cepas diferentes para здоровый кишечник y sistema inmunol&oacute;gico.&quot;,
                &quot;short_description&quot;: &quot;Probi&oacute;ticos 50B UFC, 10 cepas&quot;,
                &quot;shortDescription&quot;: &quot;Probi&oacute;ticos 50B UFC, 10 cepas&quot;,
                &quot;price&quot;: 58,
                &quot;regular_price&quot;: &quot;68.00&quot;,
                &quot;regularPrice&quot;: 68,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 120,
                &quot;stock&quot;: 120,
                &quot;weight&quot;: 0.08,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.71,
                &quot;ratingPromedio&quot;: 4.3,
                &quot;ratingCount&quot;: 91,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;9&quot;,
                &quot;name&quot;: &quot;Aceite Esencial de Lavanda&quot;,
                &quot;slug&quot;: &quot;aceite-esencial-lavanda&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-002&quot;,
                &quot;category&quot;: &quot;cuidado-personal&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Cuidado Personal&quot;,
                        &quot;slug&quot;: &quot;cuidado-personal&quot;
                    },
                    {
                        &quot;id&quot;: 7,
                        &quot;name&quot;: &quot;Aceites Esenciales&quot;,
                        &quot;slug&quot;: &quot;aceites-esenciales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Aceite esencial de lavanda 100% puro, destilado al vapor. Ideal para aromaterapia, relajaci&oacute;n y cuidado de la piel.&quot;,
                &quot;short_description&quot;: &quot;Aceite esencial 100% puro de lavanda&quot;,
                &quot;shortDescription&quot;: &quot;Aceite esencial 100% puro de lavanda&quot;,
                &quot;price&quot;: 22,
                &quot;regular_price&quot;: &quot;26.00&quot;,
                &quot;regularPrice&quot;: 26,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 95,
                &quot;stock&quot;: 95,
                &quot;weight&quot;: 0.05,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.38,
                &quot;ratingPromedio&quot;: 4.8,
                &quot;ratingCount&quot;: 178,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;10&quot;,
                &quot;name&quot;: &quot;Miel de Abeja Silvestre&quot;,
                &quot;slug&quot;: &quot;miel-abeja-silvestre&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-002&quot;,
                &quot;category&quot;: &quot;alimentos-organicos&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Alimentos Org&aacute;nicos&quot;,
                        &quot;slug&quot;: &quot;alimentos-organicos&quot;
                    },
                    {
                        &quot;id&quot;: 8,
                        &quot;name&quot;: &quot;Productos Naturales&quot;,
                        &quot;slug&quot;: &quot;productos-naturales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Miel pura de abeja silvestre, cosechada en zonas libres de pesticides. Sabor intenso y propiedades antibacterianas naturales.&quot;,
                &quot;short_description&quot;: &quot;Miel pura de abeja silvestre, libre de pesticides&quot;,
                &quot;shortDescription&quot;: &quot;Miel pura de abeja silvestre, libre de pesticides&quot;,
                &quot;price&quot;: 38,
                &quot;regular_price&quot;: &quot;45.00&quot;,
                &quot;regularPrice&quot;: 45,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 70,
                &quot;stock&quot;: 70,
                &quot;weight&quot;: 0.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.56,
                &quot;ratingPromedio&quot;: 4.9,
                &quot;ratingCount&quot;: 245,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;11&quot;,
                &quot;name&quot;: &quot;Tijeras de Poda de Acero Inoxidable&quot;,
                &quot;slug&quot;: &quot;tijeras-poda-acero-inoxidable&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-002&quot;,
                &quot;category&quot;: &quot;herramientas&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 3,
                        &quot;name&quot;: &quot;Herramientas&quot;,
                        &quot;slug&quot;: &quot;herramientas&quot;
                    }
                ],
                &quot;description&quot;: &quot;Tijeras de poda profesionales de acero inoxidable, ergon&oacute;micas yafiladas. Ideales para Bons&aacute;i y plantas de interior.&quot;,
                &quot;short_description&quot;: &quot;Tijeras de poda profesionales de acero&quot;,
                &quot;shortDescription&quot;: &quot;Tijeras de poda profesionales de acero&quot;,
                &quot;price&quot;: 28,
                &quot;regular_price&quot;: &quot;28.00&quot;,
                &quot;regularPrice&quot;: 28,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 55,
                &quot;stock&quot;: 55,
                &quot;weight&quot;: 0.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 0,
                &quot;ratingPromedio&quot;: 4.2,
                &quot;ratingCount&quot;: 44,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;12&quot;,
                &quot;name&quot;: &quot;Crema Hidratante de Aloe Vera&quot;,
                &quot;slug&quot;: &quot;crema-hidratante-aloe-vera&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-002&quot;,
                &quot;category&quot;: &quot;cuidado-personal&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Cuidado Personal&quot;,
                        &quot;slug&quot;: &quot;cuidado-personal&quot;
                    },
                    {
                        &quot;id&quot;: 8,
                        &quot;name&quot;: &quot;Productos Naturales&quot;,
                        &quot;slug&quot;: &quot;productos-naturales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Crema facial hidratante con aloe vera org&aacute;nico y aceite de jojoba. Sin parabenos, Cruelty-free y vegana.&quot;,
                &quot;short_description&quot;: &quot;Crema hidratante org&aacute;nica con aloe vera&quot;,
                &quot;shortDescription&quot;: &quot;Crema hidratante org&aacute;nica con aloe vera&quot;,
                &quot;price&quot;: 42,
                &quot;regular_price&quot;: &quot;48.00&quot;,
                &quot;regularPrice&quot;: 48,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 88,
                &quot;stock&quot;: 88,
                &quot;weight&quot;: 0.1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 12.5,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 132,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;13&quot;,
                &quot;name&quot;: &quot;Abono de Lombriz 5kg&quot;,
                &quot;slug&quot;: &quot;abono-lombriz-5kg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-002&quot;,
                &quot;category&quot;: &quot;fertilizantes&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 2,
                        &quot;name&quot;: &quot;Fertilizantes&quot;,
                        &quot;slug&quot;: &quot;fertilizantes&quot;
                    }
                ],
                &quot;description&quot;: &quot;Abono org&aacute;nico de lombriz, mejorado con microorganismos эффективных. Ideal para huerto y jard&iacute;n.&quot;,
                &quot;short_description&quot;: &quot;Abono org&aacute;nico de lombriz EM&quot;,
                &quot;shortDescription&quot;: &quot;Abono org&aacute;nico de lombriz EM&quot;,
                &quot;price&quot;: 25,
                &quot;regular_price&quot;: &quot;30.00&quot;,
                &quot;regularPrice&quot;: 30,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 110,
                &quot;stock&quot;: 110,
                &quot;weight&quot;: 5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.67,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 77,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;14&quot;,
                &quot;name&quot;: &quot;Cacao Puro en Polvo&quot;,
                &quot;slug&quot;: &quot;cacao-puro-polvo&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-003&quot;,
                &quot;category&quot;: &quot;alimentos-organicos&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Alimentos Org&aacute;nicos&quot;,
                        &quot;slug&quot;: &quot;alimentos-organicos&quot;
                    }
                ],
                &quot;description&quot;: &quot;Cacao en polvo 100% puro, sin az&uacute;car a&ntilde;adida. Rico en antioxidantes y magnesio. Procedente de comercio justo.&quot;,
                &quot;short_description&quot;: &quot;Cacao puro 100%, comercio justo&quot;,
                &quot;shortDescription&quot;: &quot;Cacao puro 100%, comercio justo&quot;,
                &quot;price&quot;: 19,
                &quot;regular_price&quot;: &quot;24.00&quot;,
                &quot;regularPrice&quot;: 24,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 140,
                &quot;stock&quot;: 140,
                &quot;weight&quot;: 0.25,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 20.83,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 198,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;15&quot;,
                &quot;name&quot;: &quot;Kit de Cosm&eacute;tica Natural DIY&quot;,
                &quot;slug&quot;: &quot;kit-cosmetica-natural-diy&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-003&quot;,
                &quot;category&quot;: &quot;cuidado-personal&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Cuidado Personal&quot;,
                        &quot;slug&quot;: &quot;cuidado-personal&quot;
                    },
                    {
                        &quot;id&quot;: 8,
                        &quot;name&quot;: &quot;Productos Naturales&quot;,
                        &quot;slug&quot;: &quot;productos-naturales&quot;
                    }
                ],
                &quot;description&quot;: &quot;Kit completo para elaborar tus propios productos de cosm&eacute;tica natural en casa. Incluye arcilla, aceites y recetas.&quot;,
                &quot;short_description&quot;: &quot;Kit DIY de cosm&eacute;tica natural con 5 recetas&quot;,
                &quot;shortDescription&quot;: &quot;Kit DIY de cosm&eacute;tica natural con 5 recetas&quot;,
                &quot;price&quot;: 65,
                &quot;regular_price&quot;: &quot;75.00&quot;,
                &quot;regularPrice&quot;: 75,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 30,
                &quot;stock&quot;: 30,
                &quot;weight&quot;: 1.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 13.33,
                &quot;ratingPromedio&quot;: 4.4,
                &quot;ratingCount&quot;: 25,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;16&quot;,
                &quot;name&quot;: &quot;Guano de Murci&eacute;lago 1kg&quot;,
                &quot;slug&quot;: &quot;guano-murcielago-1kg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-003&quot;,
                &quot;category&quot;: &quot;fertilizantes&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 2,
                        &quot;name&quot;: &quot;Fertilizantes&quot;,
                        &quot;slug&quot;: &quot;fertilizantes&quot;
                    }
                ],
                &quot;description&quot;: &quot;Fertilizante natural de guano de murci&eacute;lago, alto en f&oacute;sforo y potasio. Ideal para floraci&oacute;n y fructificaci&oacute;n.&quot;,
                &quot;short_description&quot;: &quot;Guano de murci&eacute;lago rico en P y K&quot;,
                &quot;shortDescription&quot;: &quot;Guano de murci&eacute;lago rico en P y K&quot;,
                &quot;price&quot;: 15,
                &quot;regular_price&quot;: &quot;18.00&quot;,
                &quot;regularPrice&quot;: 18,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 180,
                &quot;stock&quot;: 180,
                &quot;weight&quot;: 1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.67,
                &quot;ratingPromedio&quot;: 4.3,
                &quot;ratingCount&quot;: 56,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;17&quot;,
                &quot;name&quot;: &quot;Aspersor de Jard&iacute;n Giratorio&quot;,
                &quot;slug&quot;: &quot;aspersor-jardin-giratorio&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-003&quot;,
                &quot;category&quot;: &quot;herramientas&quot;,
                &quot;categories&quot;: [
                    {
                        &quot;id&quot;: 3,
                        &quot;name&quot;: &quot;Herramientas&quot;,
                        &quot;slug&quot;: &quot;herramientas&quot;
                    }
                ],
                &quot;description&quot;: &quot;Aspersor oscilante de jard&iacute;n con 18 boquillas, cobertura uniforme. Resistente a UV y duradero.&quot;,
                &quot;short_description&quot;: &quot;Aspersor oscilante 18 boquillas&quot;,
                &quot;shortDescription&quot;: &quot;Aspersor oscilante 18 boquillas&quot;,
                &quot;price&quot;: 35,
                &quot;regular_price&quot;: &quot;40.00&quot;,
                &quot;regularPrice&quot;: 40,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 65,
                &quot;stock&quot;: 65,
                &quot;weight&quot;: 0.8,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 12.5,
                &quot;ratingPromedio&quot;: 4.1,
                &quot;ratingCount&quot;: 33,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;mainAttributes&quot;: [],
                &quot;additionalAttributes&quot;: [],
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            }
        ],
        &quot;pagination&quot;: {
            &quot;page&quot;: 1,
            &quot;perPage&quot;: 15,
            &quot;total&quot;: 20,
            &quot;totalPages&quot;: 2,
            &quot;hasMore&quot;: true
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-products" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-products"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-products"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-products" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-products">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-products" data-method="GET"
      data-path="api/products"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-products', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-products"
                    onclick="tryItOut('GETapi-products');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-products"
                    onclick="cancelTryOut('GETapi-products');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-products"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/products</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-products"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-products"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-products--id-">GET /api/products/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-products--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/products/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-products--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: &quot;1&quot;,
        &quot;name&quot;: &quot;Colageno Marino Premium&quot;,
        &quot;slug&quot;: &quot;colageno-marino&quot;,
        &quot;type&quot;: &quot;physical&quot;,
        &quot;sku&quot;: null,
        &quot;category&quot;: &quot;&quot;,
        &quot;categories&quot;: [],
        &quot;description&quot;: &quot;Colageno marino hidrolizado de alta pureza. Ideal para la salud de la piel, articulaciones y huesos.&quot;,
        &quot;short_description&quot;: &quot;Colageno marino de grado farmaceutico.&quot;,
        &quot;shortDescription&quot;: &quot;Colageno marino de grado farmaceutico.&quot;,
        &quot;price&quot;: 89.9,
        &quot;regular_price&quot;: &quot;120.00&quot;,
        &quot;regularPrice&quot;: 120,
        &quot;sale_price&quot;: &quot;89.90&quot;,
        &quot;salePrice&quot;: 89.9,
        &quot;stock_quantity&quot;: 50,
        &quot;stock&quot;: 50,
        &quot;weight&quot;: null,
        &quot;dimensions&quot;: null,
        &quot;image&quot;: &quot;&quot;,
        &quot;images&quot;: [],
        &quot;sticker&quot;: null,
        &quot;discountPercentage&quot;: null,
        &quot;ratingPromedio&quot;: 0,
        &quot;ratingCount&quot;: 0,
        &quot;status&quot;: &quot;pending_review&quot;,
        &quot;mainAttributes&quot;: [],
        &quot;additionalAttributes&quot;: [],
        &quot;createdAt&quot;: &quot;2026-03-20T00:36:24+00:00&quot;,
        &quot;updatedAt&quot;: &quot;2026-03-20T00:36:24+00:00&quot;,
        &quot;storeId&quot;: 1
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-products--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-products--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-products--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-products--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-products--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-products--id-" data-method="GET"
      data-path="api/products/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-products--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-products--id-"
                    onclick="tryItOut('GETapi-products--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-products--id-"
                    onclick="cancelTryOut('GETapi-products--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-products--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/products/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-products--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-products--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-products--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-search">GET /api/search
Unified search with optional ?type=all returning products + categories + total_hits.</h2>

<p>
</p>

<p>Falls back to database search if Scout/Meilisearch is unavailable.</p>

<span id="example-requests-GETapi-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/search" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"q\": \"vmqeopfuudtdsufvyvddq\",
    \"type\": \"products\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/search"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "q": "vmqeopfuudtdsufvyvddq",
    "type": "products"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-search">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;products&quot;: [
            {
                &quot;id&quot;: &quot;2&quot;,
                &quot;name&quot;: &quot;Semillas de Ch&iacute;a Org&aacute;nicas&quot;,
                &quot;slug&quot;: &quot;semillas-chia-organicas&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SEM-001&quot;,
                &quot;description&quot;: &quot;Semillas de ch&iacute;a certificadas org&aacute;nicas, ricas en omega-3 y antioxidantes. Ideales para smoothies, bowls y recetas saludables.&quot;,
                &quot;short_description&quot;: &quot;Semillas org&aacute;nicas ricas en omega-3&quot;,
                &quot;shortDescription&quot;: &quot;Semillas org&aacute;nicas ricas en omega-3&quot;,
                &quot;price&quot;: 24.9,
                &quot;regular_price&quot;: &quot;29.90&quot;,
                &quot;regularPrice&quot;: 29.9,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 150,
                &quot;stock&quot;: 150,
                &quot;weight&quot;: 0.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.72,
                &quot;ratingPromedio&quot;: 4.8,
                &quot;ratingCount&quot;: 124,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:42:46+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:42:46+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;3&quot;,
                &quot;name&quot;: &quot;Aceite de Coco Virgen Extra&quot;,
                &quot;slug&quot;: &quot;aceite-coco-virgen-extra&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-001&quot;,
                &quot;description&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% puro y org&aacute;nico. Perfecto para cocinar, cosm&eacute;tica natural y cuidado del cabello.&quot;,
                &quot;short_description&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% org&aacute;nico&quot;,
                &quot;shortDescription&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% org&aacute;nico&quot;,
                &quot;price&quot;: 32,
                &quot;regular_price&quot;: &quot;38.00&quot;,
                &quot;regularPrice&quot;: 38,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 80,
                &quot;stock&quot;: 80,
                &quot;weight&quot;: 1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.79,
                &quot;ratingPromedio&quot;: 4.9,
                &quot;ratingCount&quot;: 89,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;4&quot;,
                &quot;name&quot;: &quot;Fertilizante Org&aacute;nico L&iacute;quido&quot;,
                &quot;slug&quot;: &quot;fertilizante-organico-liquido&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-001&quot;,
                &quot;description&quot;: &quot;Fertilizante l&iacute;quido 100% org&aacute;nico para plantas dom&eacute;sticas y huertos. A base de extracto de algas marinas y compost.&quot;,
                &quot;short_description&quot;: &quot;Fertilizante l&iacute;quido org&aacute;nico para plantas&quot;,
                &quot;shortDescription&quot;: &quot;Fertilizante l&iacute;quido org&aacute;nico para plantas&quot;,
                &quot;price&quot;: 18.5,
                &quot;regular_price&quot;: &quot;22.00&quot;,
                &quot;regularPrice&quot;: 22,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 200,
                &quot;stock&quot;: 200,
                &quot;weight&quot;: 1.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.91,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 67,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;5&quot;,
                &quot;name&quot;: &quot;T&eacute; Verde Matcha Ceremonial Premium&quot;,
                &quot;slug&quot;: &quot;te-verde-matcha-ceremonial&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-001&quot;,
                &quot;description&quot;: &quot;Matcha ceremonial de primera calidad, cultivado en sombra para m&aacute;xima concentraci&oacute;n de clorofila y antioxidantes.&quot;,
                &quot;short_description&quot;: &quot;Matcha ceremonial premium de Jap&oacute;n&quot;,
                &quot;shortDescription&quot;: &quot;Matcha ceremonial premium de Jap&oacute;n&quot;,
                &quot;price&quot;: 45,
                &quot;regular_price&quot;: &quot;55.00&quot;,
                &quot;regularPrice&quot;: 55,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 60,
                &quot;stock&quot;: 60,
                &quot;weight&quot;: 0.1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 18.18,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 203,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;6&quot;,
                &quot;name&quot;: &quot;Jab&oacute;n de Castilla Natural&quot;,
                &quot;slug&quot;: &quot;jabon-castilla-natural&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-001&quot;,
                &quot;description&quot;: &quot;Jab&oacute;n artesanal de aceite de oliva, hipoalerg&eacute;nico y sin fragancias. Apto para pieles sensibles y uso diario.&quot;,
                &quot;short_description&quot;: &quot;Jab&oacute;n artesanal hipoalerg&eacute;nico&quot;,
                &quot;shortDescription&quot;: &quot;Jab&oacute;n artesanal hipoalerg&eacute;nico&quot;,
                &quot;price&quot;: 12,
                &quot;regular_price&quot;: &quot;14.00&quot;,
                &quot;regularPrice&quot;: 14,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 300,
                &quot;stock&quot;: 300,
                &quot;weight&quot;: 0.15,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.29,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 156,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;7&quot;,
                &quot;name&quot;: &quot;Kit de Semillas para Huerto Urbano&quot;,
                &quot;slug&quot;: &quot;kit-semillas-huerto-urbano&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-001&quot;,
                &quot;description&quot;: &quot;Kit con 10 variedades de semillas org&aacute;nicas para cultivar tu propio huerto en casa. Incluye tomate, lechuga, albahaca, pimiento y m&aacute;s.&quot;,
                &quot;short_description&quot;: &quot;10 variedades de semillas para huerto urbano&quot;,
                &quot;shortDescription&quot;: &quot;10 variedades de semillas para huerto urbano&quot;,
                &quot;price&quot;: 35,
                &quot;regular_price&quot;: &quot;35.00&quot;,
                &quot;regularPrice&quot;: 35,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 45,
                &quot;stock&quot;: 45,
                &quot;weight&quot;: 0.3,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 0,
                &quot;ratingPromedio&quot;: 4.4,
                &quot;ratingCount&quot;: 38,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;8&quot;,
                &quot;name&quot;: &quot;Probi&oacute;ticos Flora Intestinal&quot;,
                &quot;slug&quot;: &quot;probi&oacute;ticos-flora-intestinal&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SUP-001&quot;,
                &quot;description&quot;: &quot;Suplemento de probi&oacute;ticos con 50 mil millones de UFC. 10 cepas diferentes para здоровый кишечник y sistema inmunol&oacute;gico.&quot;,
                &quot;short_description&quot;: &quot;Probi&oacute;ticos 50B UFC, 10 cepas&quot;,
                &quot;shortDescription&quot;: &quot;Probi&oacute;ticos 50B UFC, 10 cepas&quot;,
                &quot;price&quot;: 58,
                &quot;regular_price&quot;: &quot;68.00&quot;,
                &quot;regularPrice&quot;: 68,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 120,
                &quot;stock&quot;: 120,
                &quot;weight&quot;: 0.08,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.71,
                &quot;ratingPromedio&quot;: 4.3,
                &quot;ratingCount&quot;: 91,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;9&quot;,
                &quot;name&quot;: &quot;Aceite Esencial de Lavanda&quot;,
                &quot;slug&quot;: &quot;aceite-esencial-lavanda&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-002&quot;,
                &quot;description&quot;: &quot;Aceite esencial de lavanda 100% puro, destilado al vapor. Ideal para aromaterapia, relajaci&oacute;n y cuidado de la piel.&quot;,
                &quot;short_description&quot;: &quot;Aceite esencial 100% puro de lavanda&quot;,
                &quot;shortDescription&quot;: &quot;Aceite esencial 100% puro de lavanda&quot;,
                &quot;price&quot;: 22,
                &quot;regular_price&quot;: &quot;26.00&quot;,
                &quot;regularPrice&quot;: 26,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 95,
                &quot;stock&quot;: 95,
                &quot;weight&quot;: 0.05,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.38,
                &quot;ratingPromedio&quot;: 4.8,
                &quot;ratingCount&quot;: 178,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;10&quot;,
                &quot;name&quot;: &quot;Miel de Abeja Silvestre&quot;,
                &quot;slug&quot;: &quot;miel-abeja-silvestre&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-002&quot;,
                &quot;description&quot;: &quot;Miel pura de abeja silvestre, cosechada en zonas libres de pesticides. Sabor intenso y propiedades antibacterianas naturales.&quot;,
                &quot;short_description&quot;: &quot;Miel pura de abeja silvestre, libre de pesticides&quot;,
                &quot;shortDescription&quot;: &quot;Miel pura de abeja silvestre, libre de pesticides&quot;,
                &quot;price&quot;: 38,
                &quot;regular_price&quot;: &quot;45.00&quot;,
                &quot;regularPrice&quot;: 45,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 70,
                &quot;stock&quot;: 70,
                &quot;weight&quot;: 0.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.56,
                &quot;ratingPromedio&quot;: 4.9,
                &quot;ratingCount&quot;: 245,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;11&quot;,
                &quot;name&quot;: &quot;Tijeras de Poda de Acero Inoxidable&quot;,
                &quot;slug&quot;: &quot;tijeras-poda-acero-inoxidable&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-002&quot;,
                &quot;description&quot;: &quot;Tijeras de poda profesionales de acero inoxidable, ergon&oacute;micas yafiladas. Ideales para Bons&aacute;i y plantas de interior.&quot;,
                &quot;short_description&quot;: &quot;Tijeras de poda profesionales de acero&quot;,
                &quot;shortDescription&quot;: &quot;Tijeras de poda profesionales de acero&quot;,
                &quot;price&quot;: 28,
                &quot;regular_price&quot;: &quot;28.00&quot;,
                &quot;regularPrice&quot;: 28,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 55,
                &quot;stock&quot;: 55,
                &quot;weight&quot;: 0.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 0,
                &quot;ratingPromedio&quot;: 4.2,
                &quot;ratingCount&quot;: 44,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;12&quot;,
                &quot;name&quot;: &quot;Crema Hidratante de Aloe Vera&quot;,
                &quot;slug&quot;: &quot;crema-hidratante-aloe-vera&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-002&quot;,
                &quot;description&quot;: &quot;Crema facial hidratante con aloe vera org&aacute;nico y aceite de jojoba. Sin parabenos, Cruelty-free y vegana.&quot;,
                &quot;short_description&quot;: &quot;Crema hidratante org&aacute;nica con aloe vera&quot;,
                &quot;shortDescription&quot;: &quot;Crema hidratante org&aacute;nica con aloe vera&quot;,
                &quot;price&quot;: 42,
                &quot;regular_price&quot;: &quot;48.00&quot;,
                &quot;regularPrice&quot;: 48,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 88,
                &quot;stock&quot;: 88,
                &quot;weight&quot;: 0.1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 12.5,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 132,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;13&quot;,
                &quot;name&quot;: &quot;Abono de Lombriz 5kg&quot;,
                &quot;slug&quot;: &quot;abono-lombriz-5kg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-002&quot;,
                &quot;description&quot;: &quot;Abono org&aacute;nico de lombriz, mejorado con microorganismos эффективных. Ideal para huerto y jard&iacute;n.&quot;,
                &quot;short_description&quot;: &quot;Abono org&aacute;nico de lombriz EM&quot;,
                &quot;shortDescription&quot;: &quot;Abono org&aacute;nico de lombriz EM&quot;,
                &quot;price&quot;: 25,
                &quot;regular_price&quot;: &quot;30.00&quot;,
                &quot;regularPrice&quot;: 30,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 110,
                &quot;stock&quot;: 110,
                &quot;weight&quot;: 5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.67,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 77,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;14&quot;,
                &quot;name&quot;: &quot;Cacao Puro en Polvo&quot;,
                &quot;slug&quot;: &quot;cacao-puro-polvo&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-003&quot;,
                &quot;description&quot;: &quot;Cacao en polvo 100% puro, sin az&uacute;car a&ntilde;adida. Rico en antioxidantes y magnesio. Procedente de comercio justo.&quot;,
                &quot;short_description&quot;: &quot;Cacao puro 100%, comercio justo&quot;,
                &quot;shortDescription&quot;: &quot;Cacao puro 100%, comercio justo&quot;,
                &quot;price&quot;: 19,
                &quot;regular_price&quot;: &quot;24.00&quot;,
                &quot;regularPrice&quot;: 24,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 140,
                &quot;stock&quot;: 140,
                &quot;weight&quot;: 0.25,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 20.83,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 198,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;15&quot;,
                &quot;name&quot;: &quot;Kit de Cosm&eacute;tica Natural DIY&quot;,
                &quot;slug&quot;: &quot;kit-cosmetica-natural-diy&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-003&quot;,
                &quot;description&quot;: &quot;Kit completo para elaborar tus propios productos de cosm&eacute;tica natural en casa. Incluye arcilla, aceites y recetas.&quot;,
                &quot;short_description&quot;: &quot;Kit DIY de cosm&eacute;tica natural con 5 recetas&quot;,
                &quot;shortDescription&quot;: &quot;Kit DIY de cosm&eacute;tica natural con 5 recetas&quot;,
                &quot;price&quot;: 65,
                &quot;regular_price&quot;: &quot;75.00&quot;,
                &quot;regularPrice&quot;: 75,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 30,
                &quot;stock&quot;: 30,
                &quot;weight&quot;: 1.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 13.33,
                &quot;ratingPromedio&quot;: 4.4,
                &quot;ratingCount&quot;: 25,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;16&quot;,
                &quot;name&quot;: &quot;Guano de Murci&eacute;lago 1kg&quot;,
                &quot;slug&quot;: &quot;guano-murcielago-1kg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-003&quot;,
                &quot;description&quot;: &quot;Fertilizante natural de guano de murci&eacute;lago, alto en f&oacute;sforo y potasio. Ideal para floraci&oacute;n y fructificaci&oacute;n.&quot;,
                &quot;short_description&quot;: &quot;Guano de murci&eacute;lago rico en P y K&quot;,
                &quot;shortDescription&quot;: &quot;Guano de murci&eacute;lago rico en P y K&quot;,
                &quot;price&quot;: 15,
                &quot;regular_price&quot;: &quot;18.00&quot;,
                &quot;regularPrice&quot;: 18,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 180,
                &quot;stock&quot;: 180,
                &quot;weight&quot;: 1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.67,
                &quot;ratingPromedio&quot;: 4.3,
                &quot;ratingCount&quot;: 56,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;17&quot;,
                &quot;name&quot;: &quot;Aspersor de Jard&iacute;n Giratorio&quot;,
                &quot;slug&quot;: &quot;aspersor-jardin-giratorio&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-003&quot;,
                &quot;description&quot;: &quot;Aspersor oscilante de jard&iacute;n con 18 boquillas, cobertura uniforme. Resistente a UV y duradero.&quot;,
                &quot;short_description&quot;: &quot;Aspersor oscilante 18 boquillas&quot;,
                &quot;shortDescription&quot;: &quot;Aspersor oscilante 18 boquillas&quot;,
                &quot;price&quot;: 35,
                &quot;regular_price&quot;: &quot;40.00&quot;,
                &quot;regularPrice&quot;: 40,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 65,
                &quot;stock&quot;: 65,
                &quot;weight&quot;: 0.8,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 12.5,
                &quot;ratingPromedio&quot;: 4.1,
                &quot;ratingCount&quot;: 33,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;18&quot;,
                &quot;name&quot;: &quot;Vitamina C Liposomal 1000mg&quot;,
                &quot;slug&quot;: &quot;vitamina-c-liposomal-1000mg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SUP-002&quot;,
                &quot;description&quot;: &quot;Suplemento de vitamina C en presentaci&oacute;n liposomal para mejor absorci&oacute;n. Con escaramujo y bioflavonoides.&quot;,
                &quot;short_description&quot;: &quot;Vitamina C liposomial de alta absorci&oacute;n&quot;,
                &quot;shortDescription&quot;: &quot;Vitamina C liposomial de alta absorci&oacute;n&quot;,
                &quot;price&quot;: 48,
                &quot;regular_price&quot;: &quot;55.00&quot;,
                &quot;regularPrice&quot;: 55,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 100,
                &quot;stock&quot;: 100,
                &quot;weight&quot;: 0.15,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 12.73,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 142,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;19&quot;,
                &quot;name&quot;: &quot;Hidrolato de Rosa Mosqueta&quot;,
                &quot;slug&quot;: &quot;hidrolato-rosa-mosqueta&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-003&quot;,
                &quot;description&quot;: &quot;Hidrolato puro de rosa mosqueta,lenador y nutritivo. Excelente t&oacute;nico facial y fijador de maquillaje natural.&quot;,
                &quot;short_description&quot;: &quot;Hidrolato puro de rosa mosqueta&quot;,
                &quot;shortDescription&quot;: &quot;Hidrolato puro de rosa mosqueta&quot;,
                &quot;price&quot;: 28,
                &quot;regular_price&quot;: &quot;33.00&quot;,
                &quot;regularPrice&quot;: 33,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 75,
                &quot;stock&quot;: 75,
                &quot;weight&quot;: 0.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.15,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 88,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;20&quot;,
                &quot;name&quot;: &quot;Grindelia Segura para Resfriados&quot;,
                &quot;slug&quot;: &quot;grindelia-segura-resfriados&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SUP-003&quot;,
                &quot;description&quot;: &quot;Tintura madre de grindelia, planta expectorante y antiespasm&oacute;dica. Para toss y congesti&oacute;n respiratoria.&quot;,
                &quot;short_description&quot;: &quot;Tintura de grindelia para vias respiratorias&quot;,
                &quot;shortDescription&quot;: &quot;Tintura de grindelia para vias respiratorias&quot;,
                &quot;price&quot;: 19,
                &quot;regular_price&quot;: &quot;22.00&quot;,
                &quot;regularPrice&quot;: 22,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 90,
                &quot;stock&quot;: 90,
                &quot;weight&quot;: 0.05,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 13.64,
                &quot;ratingPromedio&quot;: 4.2,
                &quot;ratingCount&quot;: 41,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;21&quot;,
                &quot;name&quot;: &quot;Polen de Abeja Granulado&quot;,
                &quot;slug&quot;: &quot;polen-abeja-granulado&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-004&quot;,
                &quot;description&quot;: &quot;Polen de abeja fresco y congelado, seco. Rico en prote&iacute;nas, vitaminas del grupo B y amino&aacute;cidos esenciales.&quot;,
                &quot;short_description&quot;: &quot;Polen de abeja fresco, rico en prote&iacute;nas&quot;,
                &quot;shortDescription&quot;: &quot;Polen de abeja fresco, rico en prote&iacute;nas&quot;,
                &quot;price&quot;: 29,
                &quot;regular_price&quot;: &quot;34.00&quot;,
                &quot;regularPrice&quot;: 34,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 50,
                &quot;stock&quot;: 50,
                &quot;weight&quot;: 0.25,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.71,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 67,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            }
        ],
        &quot;categories&quot;: [],
        &quot;total_hits&quot;: 20,
        &quot;processing_time_ms&quot;: 0
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-search" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-search"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-search"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-search" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-search">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-search" data-method="GET"
      data-path="api/search"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-search', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-search"
                    onclick="tryItOut('GETapi-search');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-search"
                    onclick="cancelTryOut('GETapi-search');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-search"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-search"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="GETapi-search"
               value="products"
               data-component="body">
    <br>
<p>Example: <code>products</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>all</code></li> <li><code>products</code></li> <li><code>categories</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-search-products">GET /api/search/products
Search products with filters. Falls back to database search if Scout is unavailable.</h2>

<p>
</p>



<span id="example-requests-GETapi-search-products">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/search/products" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"q\": \"vmqeopfuudtdsufvyvddq\",
    \"category\": \"consequatur\",
    \"inStock\": \"consequatur\",
    \"minPrice\": 45,
    \"maxPrice\": 56,
    \"sticker\": \"consequatur\",
    \"per_page\": 13
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/search/products"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "q": "vmqeopfuudtdsufvyvddq",
    "category": "consequatur",
    "inStock": "consequatur",
    "minPrice": 45,
    "maxPrice": 56,
    "sticker": "consequatur",
    "per_page": 13
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-search-products">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;data&quot;: [
            {
                &quot;id&quot;: &quot;2&quot;,
                &quot;name&quot;: &quot;Semillas de Ch&iacute;a Org&aacute;nicas&quot;,
                &quot;slug&quot;: &quot;semillas-chia-organicas&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SEM-001&quot;,
                &quot;description&quot;: &quot;Semillas de ch&iacute;a certificadas org&aacute;nicas, ricas en omega-3 y antioxidantes. Ideales para smoothies, bowls y recetas saludables.&quot;,
                &quot;short_description&quot;: &quot;Semillas org&aacute;nicas ricas en omega-3&quot;,
                &quot;shortDescription&quot;: &quot;Semillas org&aacute;nicas ricas en omega-3&quot;,
                &quot;price&quot;: 24.9,
                &quot;regular_price&quot;: &quot;29.90&quot;,
                &quot;regularPrice&quot;: 29.9,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 150,
                &quot;stock&quot;: 150,
                &quot;weight&quot;: 0.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.72,
                &quot;ratingPromedio&quot;: 4.8,
                &quot;ratingCount&quot;: 124,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:42:46+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:42:46+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;3&quot;,
                &quot;name&quot;: &quot;Aceite de Coco Virgen Extra&quot;,
                &quot;slug&quot;: &quot;aceite-coco-virgen-extra&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-001&quot;,
                &quot;description&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% puro y org&aacute;nico. Perfecto para cocinar, cosm&eacute;tica natural y cuidado del cabello.&quot;,
                &quot;short_description&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% org&aacute;nico&quot;,
                &quot;shortDescription&quot;: &quot;Aceite de coco prensado en fr&iacute;o, 100% org&aacute;nico&quot;,
                &quot;price&quot;: 32,
                &quot;regular_price&quot;: &quot;38.00&quot;,
                &quot;regularPrice&quot;: 38,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 80,
                &quot;stock&quot;: 80,
                &quot;weight&quot;: 1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.79,
                &quot;ratingPromedio&quot;: 4.9,
                &quot;ratingCount&quot;: 89,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;4&quot;,
                &quot;name&quot;: &quot;Fertilizante Org&aacute;nico L&iacute;quido&quot;,
                &quot;slug&quot;: &quot;fertilizante-organico-liquido&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-001&quot;,
                &quot;description&quot;: &quot;Fertilizante l&iacute;quido 100% org&aacute;nico para plantas dom&eacute;sticas y huertos. A base de extracto de algas marinas y compost.&quot;,
                &quot;short_description&quot;: &quot;Fertilizante l&iacute;quido org&aacute;nico para plantas&quot;,
                &quot;shortDescription&quot;: &quot;Fertilizante l&iacute;quido org&aacute;nico para plantas&quot;,
                &quot;price&quot;: 18.5,
                &quot;regular_price&quot;: &quot;22.00&quot;,
                &quot;regularPrice&quot;: 22,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 200,
                &quot;stock&quot;: 200,
                &quot;weight&quot;: 1.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.91,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 67,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;5&quot;,
                &quot;name&quot;: &quot;T&eacute; Verde Matcha Ceremonial Premium&quot;,
                &quot;slug&quot;: &quot;te-verde-matcha-ceremonial&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-001&quot;,
                &quot;description&quot;: &quot;Matcha ceremonial de primera calidad, cultivado en sombra para m&aacute;xima concentraci&oacute;n de clorofila y antioxidantes.&quot;,
                &quot;short_description&quot;: &quot;Matcha ceremonial premium de Jap&oacute;n&quot;,
                &quot;shortDescription&quot;: &quot;Matcha ceremonial premium de Jap&oacute;n&quot;,
                &quot;price&quot;: 45,
                &quot;regular_price&quot;: &quot;55.00&quot;,
                &quot;regularPrice&quot;: 55,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 60,
                &quot;stock&quot;: 60,
                &quot;weight&quot;: 0.1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 18.18,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 203,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;6&quot;,
                &quot;name&quot;: &quot;Jab&oacute;n de Castilla Natural&quot;,
                &quot;slug&quot;: &quot;jabon-castilla-natural&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-001&quot;,
                &quot;description&quot;: &quot;Jab&oacute;n artesanal de aceite de oliva, hipoalerg&eacute;nico y sin fragancias. Apto para pieles sensibles y uso diario.&quot;,
                &quot;short_description&quot;: &quot;Jab&oacute;n artesanal hipoalerg&eacute;nico&quot;,
                &quot;shortDescription&quot;: &quot;Jab&oacute;n artesanal hipoalerg&eacute;nico&quot;,
                &quot;price&quot;: 12,
                &quot;regular_price&quot;: &quot;14.00&quot;,
                &quot;regularPrice&quot;: 14,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 300,
                &quot;stock&quot;: 300,
                &quot;weight&quot;: 0.15,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.29,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 156,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;7&quot;,
                &quot;name&quot;: &quot;Kit de Semillas para Huerto Urbano&quot;,
                &quot;slug&quot;: &quot;kit-semillas-huerto-urbano&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-001&quot;,
                &quot;description&quot;: &quot;Kit con 10 variedades de semillas org&aacute;nicas para cultivar tu propio huerto en casa. Incluye tomate, lechuga, albahaca, pimiento y m&aacute;s.&quot;,
                &quot;short_description&quot;: &quot;10 variedades de semillas para huerto urbano&quot;,
                &quot;shortDescription&quot;: &quot;10 variedades de semillas para huerto urbano&quot;,
                &quot;price&quot;: 35,
                &quot;regular_price&quot;: &quot;35.00&quot;,
                &quot;regularPrice&quot;: 35,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 45,
                &quot;stock&quot;: 45,
                &quot;weight&quot;: 0.3,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 0,
                &quot;ratingPromedio&quot;: 4.4,
                &quot;ratingCount&quot;: 38,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;8&quot;,
                &quot;name&quot;: &quot;Probi&oacute;ticos Flora Intestinal&quot;,
                &quot;slug&quot;: &quot;probi&oacute;ticos-flora-intestinal&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-SUP-001&quot;,
                &quot;description&quot;: &quot;Suplemento de probi&oacute;ticos con 50 mil millones de UFC. 10 cepas diferentes para здоровый кишечник y sistema inmunol&oacute;gico.&quot;,
                &quot;short_description&quot;: &quot;Probi&oacute;ticos 50B UFC, 10 cepas&quot;,
                &quot;shortDescription&quot;: &quot;Probi&oacute;ticos 50B UFC, 10 cepas&quot;,
                &quot;price&quot;: 58,
                &quot;regular_price&quot;: &quot;68.00&quot;,
                &quot;regularPrice&quot;: 68,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 120,
                &quot;stock&quot;: 120,
                &quot;weight&quot;: 0.08,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 14.71,
                &quot;ratingPromedio&quot;: 4.3,
                &quot;ratingCount&quot;: 91,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;9&quot;,
                &quot;name&quot;: &quot;Aceite Esencial de Lavanda&quot;,
                &quot;slug&quot;: &quot;aceite-esencial-lavanda&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ACE-002&quot;,
                &quot;description&quot;: &quot;Aceite esencial de lavanda 100% puro, destilado al vapor. Ideal para aromaterapia, relajaci&oacute;n y cuidado de la piel.&quot;,
                &quot;short_description&quot;: &quot;Aceite esencial 100% puro de lavanda&quot;,
                &quot;shortDescription&quot;: &quot;Aceite esencial 100% puro de lavanda&quot;,
                &quot;price&quot;: 22,
                &quot;regular_price&quot;: &quot;26.00&quot;,
                &quot;regularPrice&quot;: 26,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 95,
                &quot;stock&quot;: 95,
                &quot;weight&quot;: 0.05,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.38,
                &quot;ratingPromedio&quot;: 4.8,
                &quot;ratingCount&quot;: 178,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;10&quot;,
                &quot;name&quot;: &quot;Miel de Abeja Silvestre&quot;,
                &quot;slug&quot;: &quot;miel-abeja-silvestre&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-002&quot;,
                &quot;description&quot;: &quot;Miel pura de abeja silvestre, cosechada en zonas libres de pesticides. Sabor intenso y propiedades antibacterianas naturales.&quot;,
                &quot;short_description&quot;: &quot;Miel pura de abeja silvestre, libre de pesticides&quot;,
                &quot;shortDescription&quot;: &quot;Miel pura de abeja silvestre, libre de pesticides&quot;,
                &quot;price&quot;: 38,
                &quot;regular_price&quot;: &quot;45.00&quot;,
                &quot;regularPrice&quot;: 45,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 70,
                &quot;stock&quot;: 70,
                &quot;weight&quot;: 0.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 15.56,
                &quot;ratingPromedio&quot;: 4.9,
                &quot;ratingCount&quot;: 245,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;11&quot;,
                &quot;name&quot;: &quot;Tijeras de Poda de Acero Inoxidable&quot;,
                &quot;slug&quot;: &quot;tijeras-poda-acero-inoxidable&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-HER-002&quot;,
                &quot;description&quot;: &quot;Tijeras de poda profesionales de acero inoxidable, ergon&oacute;micas yafiladas. Ideales para Bons&aacute;i y plantas de interior.&quot;,
                &quot;short_description&quot;: &quot;Tijeras de poda profesionales de acero&quot;,
                &quot;shortDescription&quot;: &quot;Tijeras de poda profesionales de acero&quot;,
                &quot;price&quot;: 28,
                &quot;regular_price&quot;: &quot;28.00&quot;,
                &quot;regularPrice&quot;: 28,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 55,
                &quot;stock&quot;: 55,
                &quot;weight&quot;: 0.2,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 0,
                &quot;ratingPromedio&quot;: 4.2,
                &quot;ratingCount&quot;: 44,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;12&quot;,
                &quot;name&quot;: &quot;Crema Hidratante de Aloe Vera&quot;,
                &quot;slug&quot;: &quot;crema-hidratante-aloe-vera&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-002&quot;,
                &quot;description&quot;: &quot;Crema facial hidratante con aloe vera org&aacute;nico y aceite de jojoba. Sin parabenos, Cruelty-free y vegana.&quot;,
                &quot;short_description&quot;: &quot;Crema hidratante org&aacute;nica con aloe vera&quot;,
                &quot;shortDescription&quot;: &quot;Crema hidratante org&aacute;nica con aloe vera&quot;,
                &quot;price&quot;: 42,
                &quot;regular_price&quot;: &quot;48.00&quot;,
                &quot;regularPrice&quot;: 48,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 88,
                &quot;stock&quot;: 88,
                &quot;weight&quot;: 0.1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 12.5,
                &quot;ratingPromedio&quot;: 4.5,
                &quot;ratingCount&quot;: 132,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;13&quot;,
                &quot;name&quot;: &quot;Abono de Lombriz 5kg&quot;,
                &quot;slug&quot;: &quot;abono-lombriz-5kg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-002&quot;,
                &quot;description&quot;: &quot;Abono org&aacute;nico de lombriz, mejorado con microorganismos эффективных. Ideal para huerto y jard&iacute;n.&quot;,
                &quot;short_description&quot;: &quot;Abono org&aacute;nico de lombriz EM&quot;,
                &quot;shortDescription&quot;: &quot;Abono org&aacute;nico de lombriz EM&quot;,
                &quot;price&quot;: 25,
                &quot;regular_price&quot;: &quot;30.00&quot;,
                &quot;regularPrice&quot;: 30,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 110,
                &quot;stock&quot;: 110,
                &quot;weight&quot;: 5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.67,
                &quot;ratingPromedio&quot;: 4.6,
                &quot;ratingCount&quot;: 77,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;14&quot;,
                &quot;name&quot;: &quot;Cacao Puro en Polvo&quot;,
                &quot;slug&quot;: &quot;cacao-puro-polvo&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-ALI-003&quot;,
                &quot;description&quot;: &quot;Cacao en polvo 100% puro, sin az&uacute;car a&ntilde;adida. Rico en antioxidantes y magnesio. Procedente de comercio justo.&quot;,
                &quot;short_description&quot;: &quot;Cacao puro 100%, comercio justo&quot;,
                &quot;shortDescription&quot;: &quot;Cacao puro 100%, comercio justo&quot;,
                &quot;price&quot;: 19,
                &quot;regular_price&quot;: &quot;24.00&quot;,
                &quot;regularPrice&quot;: 24,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 140,
                &quot;stock&quot;: 140,
                &quot;weight&quot;: 0.25,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 20.83,
                &quot;ratingPromedio&quot;: 4.7,
                &quot;ratingCount&quot;: 198,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;15&quot;,
                &quot;name&quot;: &quot;Kit de Cosm&eacute;tica Natural DIY&quot;,
                &quot;slug&quot;: &quot;kit-cosmetica-natural-diy&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-CUI-003&quot;,
                &quot;description&quot;: &quot;Kit completo para elaborar tus propios productos de cosm&eacute;tica natural en casa. Incluye arcilla, aceites y recetas.&quot;,
                &quot;short_description&quot;: &quot;Kit DIY de cosm&eacute;tica natural con 5 recetas&quot;,
                &quot;shortDescription&quot;: &quot;Kit DIY de cosm&eacute;tica natural con 5 recetas&quot;,
                &quot;price&quot;: 65,
                &quot;regular_price&quot;: &quot;75.00&quot;,
                &quot;regularPrice&quot;: 75,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 30,
                &quot;stock&quot;: 30,
                &quot;weight&quot;: 1.5,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 13.33,
                &quot;ratingPromedio&quot;: 4.4,
                &quot;ratingCount&quot;: 25,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            },
            {
                &quot;id&quot;: &quot;16&quot;,
                &quot;name&quot;: &quot;Guano de Murci&eacute;lago 1kg&quot;,
                &quot;slug&quot;: &quot;guano-murcielago-1kg&quot;,
                &quot;type&quot;: &quot;physical&quot;,
                &quot;sku&quot;: &quot;BIO-FER-003&quot;,
                &quot;description&quot;: &quot;Fertilizante natural de guano de murci&eacute;lago, alto en f&oacute;sforo y potasio. Ideal para floraci&oacute;n y fructificaci&oacute;n.&quot;,
                &quot;short_description&quot;: &quot;Guano de murci&eacute;lago rico en P y K&quot;,
                &quot;shortDescription&quot;: &quot;Guano de murci&eacute;lago rico en P y K&quot;,
                &quot;price&quot;: 15,
                &quot;regular_price&quot;: &quot;18.00&quot;,
                &quot;regularPrice&quot;: 18,
                &quot;sale_price&quot;: null,
                &quot;salePrice&quot;: null,
                &quot;stock_quantity&quot;: 180,
                &quot;stock&quot;: 180,
                &quot;weight&quot;: 1,
                &quot;dimensions&quot;: null,
                &quot;image&quot;: &quot;&quot;,
                &quot;images&quot;: [],
                &quot;sticker&quot;: null,
                &quot;discountPercentage&quot;: 16.67,
                &quot;ratingPromedio&quot;: 4.3,
                &quot;ratingCount&quot;: 56,
                &quot;status&quot;: &quot;approved&quot;,
                &quot;createdAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;updatedAt&quot;: &quot;2026-03-20T00:43:10+00:00&quot;,
                &quot;storeId&quot;: 1
            }
        ],
        &quot;meta&quot;: {
            &quot;current_page&quot;: 1,
            &quot;per_page&quot;: 15,
            &quot;total&quot;: 15
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-search-products" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-search-products"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-search-products"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-search-products" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-search-products">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-search-products" data-method="GET"
      data-path="api/search/products"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-search-products', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-search-products"
                    onclick="tryItOut('GETapi-search-products');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-search-products"
                    onclick="cancelTryOut('GETapi-search-products');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-search-products"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/search/products</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-search-products"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-search-products"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-search-products"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category"                data-endpoint="GETapi-search-products"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>inStock</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="inStock"                data-endpoint="GETapi-search-products"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>minPrice</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="minPrice"                data-endpoint="GETapi-search-products"
               value="45"
               data-component="body">
    <br>
<p>validation.min. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>maxPrice</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="maxPrice"                data-endpoint="GETapi-search-products"
               value="56"
               data-component="body">
    <br>
<p>validation.min. Example: <code>56</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sticker</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sticker"                data-endpoint="GETapi-search-products"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-search-products"
               value="13"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>13</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-search-suggestions">GET /api/search/suggestions
Get search suggestions/autocomplete. Falls back to database.</h2>

<p>
</p>



<span id="example-requests-GETapi-search-suggestions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/search/suggestions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"q\": \"vmqeopfuudtdsufvyvddq\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/search/suggestions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "q": "vmqeopfuudtdsufvyvddq"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-search-suggestions">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;data&quot;: [
            {
                &quot;id&quot;: 2,
                &quot;name&quot;: &quot;Semillas de Ch&iacute;a Org&aacute;nicas&quot;,
                &quot;slug&quot;: &quot;semillas-chia-organicas&quot;
            },
            {
                &quot;id&quot;: 3,
                &quot;name&quot;: &quot;Aceite de Coco Virgen Extra&quot;,
                &quot;slug&quot;: &quot;aceite-coco-virgen-extra&quot;
            },
            {
                &quot;id&quot;: 4,
                &quot;name&quot;: &quot;Fertilizante Org&aacute;nico L&iacute;quido&quot;,
                &quot;slug&quot;: &quot;fertilizante-organico-liquido&quot;
            },
            {
                &quot;id&quot;: 5,
                &quot;name&quot;: &quot;T&eacute; Verde Matcha Ceremonial Premium&quot;,
                &quot;slug&quot;: &quot;te-verde-matcha-ceremonial&quot;
            },
            {
                &quot;id&quot;: 6,
                &quot;name&quot;: &quot;Jab&oacute;n de Castilla Natural&quot;,
                &quot;slug&quot;: &quot;jabon-castilla-natural&quot;
            },
            {
                &quot;id&quot;: 7,
                &quot;name&quot;: &quot;Kit de Semillas para Huerto Urbano&quot;,
                &quot;slug&quot;: &quot;kit-semillas-huerto-urbano&quot;
            },
            {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Probi&oacute;ticos Flora Intestinal&quot;,
                &quot;slug&quot;: &quot;probi&oacute;ticos-flora-intestinal&quot;
            },
            {
                &quot;id&quot;: 9,
                &quot;name&quot;: &quot;Aceite Esencial de Lavanda&quot;,
                &quot;slug&quot;: &quot;aceite-esencial-lavanda&quot;
            },
            {
                &quot;id&quot;: 10,
                &quot;name&quot;: &quot;Miel de Abeja Silvestre&quot;,
                &quot;slug&quot;: &quot;miel-abeja-silvestre&quot;
            },
            {
                &quot;id&quot;: 11,
                &quot;name&quot;: &quot;Tijeras de Poda de Acero Inoxidable&quot;,
                &quot;slug&quot;: &quot;tijeras-poda-acero-inoxidable&quot;
            }
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-search-suggestions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-search-suggestions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-search-suggestions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-search-suggestions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-search-suggestions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-search-suggestions" data-method="GET"
      data-path="api/search/suggestions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-search-suggestions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-search-suggestions"
                    onclick="tryItOut('GETapi-search-suggestions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-search-suggestions"
                    onclick="cancelTryOut('GETapi-search-suggestions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-search-suggestions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/search/suggestions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-search-suggestions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-search-suggestions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-search-suggestions"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-users-me">GET /api/users/me</h2>

<p>
</p>



<span id="example-requests-GETapi-users-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/users/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/users/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-users-me">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-users-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-users-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-users-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-users-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-users-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-users-me" data-method="GET"
      data-path="api/users/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-users-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-users-me"
                    onclick="tryItOut('GETapi-users-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-users-me"
                    onclick="cancelTryOut('GETapi-users-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-users-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/users/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-users-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-users-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-users--id-">GET /api/users/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-users--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/users/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/users/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-users--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-users--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-users--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-users--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-users--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-users--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-users--id-" data-method="GET"
      data-path="api/users/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-users--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-users--id-"
                    onclick="tryItOut('GETapi-users--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-users--id-"
                    onclick="cancelTryOut('GETapi-users--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-users--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/users/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-users--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-users--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-users--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-users--id-">PUT /api/users/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-users--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/users/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"display_name\": \"vmqeopfuudtdsufvyvddq\",
    \"avatar\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/users/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "display_name": "vmqeopfuudtdsufvyvddq",
    "avatar": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-users--id-">
</span>
<span id="execution-results-PUTapi-users--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-users--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-users--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-users--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-users--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-users--id-" data-method="PUT"
      data-path="api/users/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-users--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-users--id-"
                    onclick="tryItOut('PUTapi-users--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-users--id-"
                    onclick="cancelTryOut('PUTapi-users--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-users--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/users/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-users--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-users--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-users--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>display_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="display_name"                data-endpoint="PUTapi-users--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-users--id-"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>avatar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="avatar"                data-endpoint="PUTapi-users--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-cart">GET api/cart</h2>

<p>
</p>



<span id="example-requests-GETapi-cart">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/cart" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/cart"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-cart">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-cart" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-cart"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-cart"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-cart" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-cart">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-cart" data-method="GET"
      data-path="api/cart"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-cart', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-cart"
                    onclick="tryItOut('GETapi-cart');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-cart"
                    onclick="cancelTryOut('GETapi-cart');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-cart"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/cart</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-cart"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-cart"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-cart-items">POST api/cart/items</h2>

<p>
</p>



<span id="example-requests-POSTapi-cart-items">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/cart/items" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"product_id\": 17,
    \"quantity\": 13
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/cart/items"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "product_id": 17,
    "quantity": 13
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-cart-items">
</span>
<span id="execution-results-POSTapi-cart-items" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-cart-items"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-cart-items"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-cart-items" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-cart-items">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-cart-items" data-method="POST"
      data-path="api/cart/items"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-cart-items', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-cart-items"
                    onclick="tryItOut('POSTapi-cart-items');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-cart-items"
                    onclick="cancelTryOut('POSTapi-cart-items');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-cart-items"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/cart/items</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-cart-items"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-cart-items"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>product_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="product_id"                data-endpoint="POSTapi-cart-items"
               value="17"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the products table. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="quantity"                data-endpoint="POSTapi-cart-items"
               value="13"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>13</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PATCHapi-cart-items--product_id-">PATCH api/cart/items/{product_id}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-cart-items--product_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/cart/items/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"quantity\": 21
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/cart/items/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "quantity": 21
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-cart-items--product_id-">
</span>
<span id="execution-results-PATCHapi-cart-items--product_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-cart-items--product_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-cart-items--product_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-cart-items--product_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-cart-items--product_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-cart-items--product_id-" data-method="PATCH"
      data-path="api/cart/items/{product_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-cart-items--product_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-cart-items--product_id-"
                    onclick="tryItOut('PATCHapi-cart-items--product_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-cart-items--product_id-"
                    onclick="cancelTryOut('PATCHapi-cart-items--product_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-cart-items--product_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/cart/items/{product_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-cart-items--product_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-cart-items--product_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>product_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="product_id"                data-endpoint="PATCHapi-cart-items--product_id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="quantity"                data-endpoint="PATCHapi-cart-items--product_id-"
               value="21"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>21</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-cart-items--product_id-">DELETE api/cart/items/{product_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-cart-items--product_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/cart/items/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/cart/items/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-cart-items--product_id-">
</span>
<span id="execution-results-DELETEapi-cart-items--product_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-cart-items--product_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-cart-items--product_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-cart-items--product_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-cart-items--product_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-cart-items--product_id-" data-method="DELETE"
      data-path="api/cart/items/{product_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-cart-items--product_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-cart-items--product_id-"
                    onclick="tryItOut('DELETEapi-cart-items--product_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-cart-items--product_id-"
                    onclick="cancelTryOut('DELETEapi-cart-items--product_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-cart-items--product_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/cart/items/{product_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-cart-items--product_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-cart-items--product_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>product_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="product_id"                data-endpoint="DELETEapi-cart-items--product_id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-cart">DELETE api/cart</h2>

<p>
</p>



<span id="example-requests-DELETEapi-cart">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/cart" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/cart"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-cart">
</span>
<span id="execution-results-DELETEapi-cart" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-cart"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-cart"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-cart" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-cart">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-cart" data-method="DELETE"
      data-path="api/cart"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-cart', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-cart"
                    onclick="tryItOut('DELETEapi-cart');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-cart"
                    onclick="cancelTryOut('DELETEapi-cart');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-cart"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/cart</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-cart"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-cart"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-orders">GET api/orders</h2>

<p>
</p>



<span id="example-requests-GETapi-orders">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/orders" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/orders"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-orders">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-orders" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-orders"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-orders"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-orders" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-orders">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-orders" data-method="GET"
      data-path="api/orders"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-orders', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-orders"
                    onclick="tryItOut('GETapi-orders');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-orders"
                    onclick="cancelTryOut('GETapi-orders');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-orders"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/orders</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-orders--id-">GET api/orders/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-orders--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/orders/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/orders/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-orders--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-orders--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-orders--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-orders--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-orders--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-orders--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-orders--id-" data-method="GET"
      data-path="api/orders/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-orders--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-orders--id-"
                    onclick="tryItOut('GETapi-orders--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-orders--id-"
                    onclick="cancelTryOut('GETapi-orders--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-orders--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/orders/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-orders--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-orders--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-orders--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the order. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-orders">POST api/orders</h2>

<p>
</p>



<span id="example-requests-POSTapi-orders">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/orders" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"payment_method\": \"vmqeopfuudtdsufvyvddq\",
    \"shipping_name\": \"amniihfqcoynlazghdtqt\",
    \"shipping_email\": \"andreanne00@example.org\",
    \"shipping_phone\": \"wbpilpmufinllwloauydl\",
    \"shipping_address\": \"smsjuryvojcybzvrbyick\",
    \"shipping_city\": \"znkygloigmkwxphlvazjr\",
    \"shipping_postal_code\": \"cnfbaqywuxhgjjmzu\",
    \"shipping_notes\": \"xjubqouzswiwxtrkimfca\",
    \"shipping_cost\": 69,
    \"notes\": \"bxspzmrazsroyjpxmqese\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/orders"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "payment_method": "vmqeopfuudtdsufvyvddq",
    "shipping_name": "amniihfqcoynlazghdtqt",
    "shipping_email": "andreanne00@example.org",
    "shipping_phone": "wbpilpmufinllwloauydl",
    "shipping_address": "smsjuryvojcybzvrbyick",
    "shipping_city": "znkygloigmkwxphlvazjr",
    "shipping_postal_code": "cnfbaqywuxhgjjmzu",
    "shipping_notes": "xjubqouzswiwxtrkimfca",
    "shipping_cost": 69,
    "notes": "bxspzmrazsroyjpxmqese"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-orders">
</span>
<span id="execution-results-POSTapi-orders" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-orders"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-orders"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-orders" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-orders">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-orders" data-method="POST"
      data-path="api/orders"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-orders', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-orders"
                    onclick="tryItOut('POSTapi-orders');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-orders"
                    onclick="cancelTryOut('POSTapi-orders');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-orders"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/orders</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>payment_method</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="payment_method"                data-endpoint="POSTapi-orders"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_name"                data-endpoint="POSTapi-orders"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>validation.max. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_email"                data-endpoint="POSTapi-orders"
               value="andreanne00@example.org"
               data-component="body">
    <br>
<p>validation.email validation.max. Example: <code>andreanne00@example.org</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_phone"                data-endpoint="POSTapi-orders"
               value="wbpilpmufinllwloauydl"
               data-component="body">
    <br>
<p>validation.max. Example: <code>wbpilpmufinllwloauydl</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_address</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_address"                data-endpoint="POSTapi-orders"
               value="smsjuryvojcybzvrbyick"
               data-component="body">
    <br>
<p>validation.max. Example: <code>smsjuryvojcybzvrbyick</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_city</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_city"                data-endpoint="POSTapi-orders"
               value="znkygloigmkwxphlvazjr"
               data-component="body">
    <br>
<p>validation.max. Example: <code>znkygloigmkwxphlvazjr</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_postal_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_postal_code"                data-endpoint="POSTapi-orders"
               value="cnfbaqywuxhgjjmzu"
               data-component="body">
    <br>
<p>validation.max. Example: <code>cnfbaqywuxhgjjmzu</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shipping_notes"                data-endpoint="POSTapi-orders"
               value="xjubqouzswiwxtrkimfca"
               data-component="body">
    <br>
<p>validation.max. Example: <code>xjubqouzswiwxtrkimfca</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shipping_cost</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="shipping_cost"                data-endpoint="POSTapi-orders"
               value="69"
               data-component="body">
    <br>
<p>validation.min. Example: <code>69</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>notes</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="notes"                data-endpoint="POSTapi-orders"
               value="bxspzmrazsroyjpxmqese"
               data-component="body">
    <br>
<p>validation.max. Example: <code>bxspzmrazsroyjpxmqese</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-orders--id--status">PUT api/orders/{id}/status</h2>

<p>
</p>



<span id="example-requests-PUTapi-orders--id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/orders/consequatur/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"vmqeopfuudtdsufvyvddq\",
    \"payment_status\": \"paid\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/orders/consequatur/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "vmqeopfuudtdsufvyvddq",
    "payment_status": "paid"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-orders--id--status">
</span>
<span id="execution-results-PUTapi-orders--id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-orders--id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-orders--id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-orders--id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-orders--id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-orders--id--status" data-method="PUT"
      data-path="api/orders/{id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-orders--id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-orders--id--status"
                    onclick="tryItOut('PUTapi-orders--id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-orders--id--status"
                    onclick="cancelTryOut('PUTapi-orders--id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-orders--id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/orders/{id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-orders--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-orders--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-orders--id--status"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the order. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-orders--id--status"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>payment_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="payment_status"                data-endpoint="PUTapi-orders--id--status"
               value="paid"
               data-component="body">
    <br>
<p>Example: <code>paid</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pending</code></li> <li><code>paid</code></li> <li><code>failed</code></li> <li><code>refunded</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-invoices">GET api/invoices</h2>

<p>
</p>



<span id="example-requests-GETapi-invoices">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/invoices" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/invoices"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-invoices">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-invoices" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-invoices"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-invoices"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-invoices" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-invoices">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-invoices" data-method="GET"
      data-path="api/invoices"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-invoices', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-invoices"
                    onclick="tryItOut('GETapi-invoices');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-invoices"
                    onclick="cancelTryOut('GETapi-invoices');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-invoices"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/invoices</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-invoices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-invoices"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-invoices--id-">GET api/invoices/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-invoices--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/invoices/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/invoices/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-invoices--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-invoices--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-invoices--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-invoices--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-invoices--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-invoices--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-invoices--id-" data-method="GET"
      data-path="api/invoices/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-invoices--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-invoices--id-"
                    onclick="tryItOut('GETapi-invoices--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-invoices--id-"
                    onclick="cancelTryOut('GETapi-invoices--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-invoices--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/invoices/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-invoices--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-invoices--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-invoices--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the invoice. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-orders--orderId--invoice">POST api/orders/{orderId}/invoice</h2>

<p>
</p>



<span id="example-requests-POSTapi-orders--orderId--invoice">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/orders/consequatur/invoice" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"nit\": \"vmqeopfuudtdsufvyvddq\",
    \"business_name\": \"amniihfqcoynlazghdtqt\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/orders/consequatur/invoice"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "nit": "vmqeopfuudtdsufvyvddq",
    "business_name": "amniihfqcoynlazghdtqt"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-orders--orderId--invoice">
</span>
<span id="execution-results-POSTapi-orders--orderId--invoice" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-orders--orderId--invoice"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-orders--orderId--invoice"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-orders--orderId--invoice" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-orders--orderId--invoice">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-orders--orderId--invoice" data-method="POST"
      data-path="api/orders/{orderId}/invoice"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-orders--orderId--invoice', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-orders--orderId--invoice"
                    onclick="tryItOut('POSTapi-orders--orderId--invoice');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-orders--orderId--invoice"
                    onclick="cancelTryOut('POSTapi-orders--orderId--invoice');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-orders--orderId--invoice"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/orders/{orderId}/invoice</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-orders--orderId--invoice"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-orders--orderId--invoice"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>orderId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="orderId"                data-endpoint="POSTapi-orders--orderId--invoice"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>nit</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="nit"                data-endpoint="POSTapi-orders--orderId--invoice"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>business_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="business_name"                data-endpoint="POSTapi-orders--orderId--invoice"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>validation.max. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-users">GET /api/users</h2>

<p>
</p>



<span id="example-requests-GETapi-users">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/users" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/users"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-users">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-users" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-users"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-users"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-users" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-users">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-users" data-method="GET"
      data-path="api/users"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-users', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-users"
                    onclick="tryItOut('GETapi-users');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-users"
                    onclick="cancelTryOut('GETapi-users');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-users"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/users</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-users-role--role-">GET /api/users/role/{role}</h2>

<p>
</p>



<span id="example-requests-GETapi-users-role--role-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/users/role/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/users/role/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-users-role--role-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-users-role--role-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-users-role--role-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-users-role--role-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-users-role--role-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-users-role--role-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-users-role--role-" data-method="GET"
      data-path="api/users/role/{role}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-users-role--role-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-users-role--role-"
                    onclick="tryItOut('GETapi-users-role--role-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-users-role--role-"
                    onclick="cancelTryOut('GETapi-users-role--role-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-users-role--role-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/users/role/{role}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-users-role--role-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-users-role--role-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="role"                data-endpoint="GETapi-users-role--role-"
               value="consequatur"
               data-component="url">
    <br>
<p>The role. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-users--id-">DELETE /api/users/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-users--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/users/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/users/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-users--id-">
</span>
<span id="execution-results-DELETEapi-users--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-users--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-users--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-users--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-users--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-users--id-" data-method="DELETE"
      data-path="api/users/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-users--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-users--id-"
                    onclick="tryItOut('DELETEapi-users--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-users--id-"
                    onclick="cancelTryOut('DELETEapi-users--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-users--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/users/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-users--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-users--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-users--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-stores">GET /api/stores</h2>

<p>
</p>



<span id="example-requests-GETapi-stores">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/stores" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-stores">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-stores" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-stores"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-stores"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-stores" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-stores">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-stores" data-method="GET"
      data-path="api/stores"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-stores', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-stores"
                    onclick="tryItOut('GETapi-stores');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-stores"
                    onclick="cancelTryOut('GETapi-stores');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-stores"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/stores</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-stores"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-stores"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-stores--id-">GET /api/stores/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-stores--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/stores/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-stores--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-stores--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-stores--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-stores--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-stores--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-stores--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-stores--id-" data-method="GET"
      data-path="api/stores/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-stores--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-stores--id-"
                    onclick="tryItOut('GETapi-stores--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-stores--id-"
                    onclick="cancelTryOut('GETapi-stores--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-stores--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/stores/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-stores--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-stores--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-stores--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the store. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-stores--id--status">PUT /api/stores/{id}/status
Admin: aprobar, rechazar o banear vendedores</h2>

<p>
</p>



<span id="example-requests-PUTapi-stores--id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/stores/consequatur/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"banned\",
    \"reason\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores/consequatur/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "banned",
    "reason": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-stores--id--status">
</span>
<span id="execution-results-PUTapi-stores--id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-stores--id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-stores--id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-stores--id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-stores--id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-stores--id--status" data-method="PUT"
      data-path="api/stores/{id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-stores--id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-stores--id--status"
                    onclick="tryItOut('PUTapi-stores--id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-stores--id--status"
                    onclick="cancelTryOut('PUTapi-stores--id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-stores--id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/stores/{id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-stores--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-stores--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-stores--id--status"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the store. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-stores--id--status"
               value="banned"
               data-component="body">
    <br>
<p>Example: <code>banned</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>approved</code></li> <li><code>rejected</code></li> <li><code>banned</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="PUTapi-stores--id--status"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-categories">POST /api/categories</h2>

<p>
</p>



<span id="example-requests-POSTapi-categories">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/categories" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"description\": \"Dolores dolorum amet iste laborum eius est dolor.\",
    \"parent\": 17,
    \"image\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/categories"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "description": "Dolores dolorum amet iste laborum eius est dolor.",
    "parent": 17,
    "image": "consequatur"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-categories">
</span>
<span id="execution-results-POSTapi-categories" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-categories"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-categories"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-categories" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-categories">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-categories" data-method="POST"
      data-path="api/categories"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-categories', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-categories"
                    onclick="tryItOut('POSTapi-categories');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-categories"
                    onclick="cancelTryOut('POSTapi-categories');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-categories"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/categories</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-categories"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-categories"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-categories"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>parent</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="parent"                data-endpoint="POSTapi-categories"
               value="17"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the categories table. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="image"                data-endpoint="POSTapi-categories"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-categories--id-">PUT /api/categories/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/categories/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"description\": \"Dolores dolorum amet iste laborum eius est dolor.\",
    \"parent\": 17,
    \"image\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/categories/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "description": "Dolores dolorum amet iste laborum eius est dolor.",
    "parent": 17,
    "image": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-categories--id-">
</span>
<span id="execution-results-PUTapi-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-categories--id-" data-method="PUT"
      data-path="api/categories/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-categories--id-"
                    onclick="tryItOut('PUTapi-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-categories--id-"
                    onclick="cancelTryOut('PUTapi-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-categories--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-categories--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-categories--id-"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>parent</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="parent"                data-endpoint="PUTapi-categories--id-"
               value="17"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the categories table. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="image"                data-endpoint="PUTapi-categories--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-categories--id-">DELETE /api/categories/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-categories--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/categories/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/categories/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-categories--id-">
</span>
<span id="execution-results-DELETEapi-categories--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-categories--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-categories--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-categories--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-categories--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-categories--id-" data-method="DELETE"
      data-path="api/categories/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-categories--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-categories--id-"
                    onclick="tryItOut('DELETEapi-categories--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-categories--id-"
                    onclick="cancelTryOut('DELETEapi-categories--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-categories--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/categories/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-categories--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-categories--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the category. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-products--id--status">PUT /api/products/{id}/status
Admin: aprobar o rechazar productos</h2>

<p>
</p>



<span id="example-requests-PUTapi-products--id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/products/1/status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"rejected\",
    \"reason\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1/status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "rejected",
    "reason": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-products--id--status">
</span>
<span id="execution-results-PUTapi-products--id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-products--id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-products--id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-products--id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-products--id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-products--id--status" data-method="PUT"
      data-path="api/products/{id}/status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-products--id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-products--id--status"
                    onclick="tryItOut('PUTapi-products--id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-products--id--status"
                    onclick="cancelTryOut('PUTapi-products--id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-products--id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/products/{id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-products--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-products--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-products--id--status"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-products--id--status"
               value="rejected"
               data-component="body">
    <br>
<p>Example: <code>rejected</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>approved</code></li> <li><code>rejected</code></li> <li><code>pending_review</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="PUTapi-products--id--status"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-seller-profile">GET /api/seller/profile
Get current seller&#039;s profile.</h2>

<p>
</p>



<span id="example-requests-GETapi-seller-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/seller/profile" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/seller/profile"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-seller-profile">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-seller-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-seller-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-seller-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-seller-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-seller-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-seller-profile" data-method="GET"
      data-path="api/seller/profile"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-seller-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-seller-profile"
                    onclick="tryItOut('GETapi-seller-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-seller-profile"
                    onclick="cancelTryOut('GETapi-seller-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-seller-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/seller/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-seller-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-seller-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-PUTapi-seller-profile">PUT /api/seller/profile
Update current seller&#039;s profile.</h2>

<p>
</p>



<span id="example-requests-PUTapi-seller-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/seller/profile" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"username\": \"amniihfqcoynlazghdtqt\",
    \"phone\": \"qxbajwbpilpmufinl\",
    \"avatar\": \"https:\\/\\/www.strosin.com\\/accusantium-et-a-qui-ducimus-nihil-laudantium\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/seller/profile"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "username": "amniihfqcoynlazghdtqt",
    "phone": "qxbajwbpilpmufinl",
    "avatar": "https:\/\/www.strosin.com\/accusantium-et-a-qui-ducimus-nihil-laudantium"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-seller-profile">
</span>
<span id="execution-results-PUTapi-seller-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-seller-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-seller-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-seller-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-seller-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-seller-profile" data-method="PUT"
      data-path="api/seller/profile"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-seller-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-seller-profile"
                    onclick="tryItOut('PUTapi-seller-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-seller-profile"
                    onclick="cancelTryOut('PUTapi-seller-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-seller-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/seller/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-seller-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-seller-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-seller-profile"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>username</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="username"                data-endpoint="PUTapi-seller-profile"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-seller-profile"
               value="qxbajwbpilpmufinl"
               data-component="body">
    <br>
<p>validation.max. Example: <code>qxbajwbpilpmufinl</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>avatar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="avatar"                data-endpoint="PUTapi-seller-profile"
               value="https://www.strosin.com/accusantium-et-a-qui-ducimus-nihil-laudantium"
               data-component="body">
    <br>
<p>Must be a valid URL. Example: <code>https://www.strosin.com/accusantium-et-a-qui-ducimus-nihil-laudantium</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-seller-store">GET /api/seller/store
Get current seller&#039;s store data.</h2>

<p>
</p>



<span id="example-requests-GETapi-seller-store">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/seller/store" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/seller/store"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-seller-store">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-seller-store" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-seller-store"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-seller-store"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-seller-store" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-seller-store">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-seller-store" data-method="GET"
      data-path="api/seller/store"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-seller-store', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-seller-store"
                    onclick="tryItOut('GETapi-seller-store');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-seller-store"
                    onclick="cancelTryOut('GETapi-seller-store');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-seller-store"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/seller/store</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-seller-store"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-seller-store"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-stores">POST /api/stores</h2>

<p>
</p>



<span id="example-requests-POSTapi-stores">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/stores" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"trade_name\": \"vmqeopfuudtdsufvyvddq\",
    \"ruc\": \"amniihfqcoy\",
    \"corporate_email\": \"agustin98@example.com\",
    \"description\": \"Dolores dolorum amet iste laborum eius est dolor.\",
    \"phone\": \"dtdsufvyvddqamnii\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "trade_name": "vmqeopfuudtdsufvyvddq",
    "ruc": "amniihfqcoy",
    "corporate_email": "agustin98@example.com",
    "description": "Dolores dolorum amet iste laborum eius est dolor.",
    "phone": "dtdsufvyvddqamnii"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-stores">
</span>
<span id="execution-results-POSTapi-stores" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-stores"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-stores"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-stores" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-stores">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-stores" data-method="POST"
      data-path="api/stores"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-stores', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-stores"
                    onclick="tryItOut('POSTapi-stores');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-stores"
                    onclick="cancelTryOut('POSTapi-stores');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-stores"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/stores</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-stores"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-stores"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>trade_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="trade_name"                data-endpoint="POSTapi-stores"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ruc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ruc"                data-endpoint="POSTapi-stores"
               value="amniihfqcoy"
               data-component="body">
    <br>
<p>validation.size. Example: <code>amniihfqcoy</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>corporate_email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="corporate_email"                data-endpoint="POSTapi-stores"
               value="agustin98@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>agustin98@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-stores"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-stores"
               value="dtdsufvyvddqamnii"
               data-component="body">
    <br>
<p>validation.max. Example: <code>dtdsufvyvddqamnii</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-stores--id-">PUT /api/stores/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-stores--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/stores/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"trade_name\": \"vmqeopfuudtdsufvyvddq\",
    \"corporate_email\": \"kunde.eloisa@example.com\",
    \"description\": \"Dolores dolorum amet iste laborum eius est dolor.\",
    \"phone\": \"dtdsufvyvddqamnii\",
    \"logo\": \"consequatur\",
    \"banner\": \"consequatur\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "trade_name": "vmqeopfuudtdsufvyvddq",
    "corporate_email": "kunde.eloisa@example.com",
    "description": "Dolores dolorum amet iste laborum eius est dolor.",
    "phone": "dtdsufvyvddqamnii",
    "logo": "consequatur",
    "banner": "consequatur"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-stores--id-">
</span>
<span id="execution-results-PUTapi-stores--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-stores--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-stores--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-stores--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-stores--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-stores--id-" data-method="PUT"
      data-path="api/stores/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-stores--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-stores--id-"
                    onclick="tryItOut('PUTapi-stores--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-stores--id-"
                    onclick="cancelTryOut('PUTapi-stores--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-stores--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/stores/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-stores--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-stores--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PUTapi-stores--id-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the store. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>trade_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="trade_name"                data-endpoint="PUTapi-stores--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>corporate_email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="corporate_email"                data-endpoint="PUTapi-stores--id-"
               value="kunde.eloisa@example.com"
               data-component="body">
    <br>
<p>validation.email. Example: <code>kunde.eloisa@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-stores--id-"
               value="Dolores dolorum amet iste laborum eius est dolor."
               data-component="body">
    <br>
<p>Example: <code>Dolores dolorum amet iste laborum eius est dolor.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-stores--id-"
               value="dtdsufvyvddqamnii"
               data-component="body">
    <br>
<p>validation.max. Example: <code>dtdsufvyvddqamnii</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>logo</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="logo"                data-endpoint="PUTapi-stores--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>banner</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="banner"                data-endpoint="PUTapi-stores--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-stores--id--media-logo">Upload store logo.</h2>

<p>
</p>

<p>POST /api/stores/{storeId}/media/logo</p>

<span id="example-requests-POSTapi-stores--id--media-logo">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/stores/consequatur/media/logo" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@C:\Users\Ing Angel\AppData\Local\Temp\phpDF97.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores/consequatur/media/logo"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-stores--id--media-logo">
</span>
<span id="execution-results-POSTapi-stores--id--media-logo" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-stores--id--media-logo"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-stores--id--media-logo"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-stores--id--media-logo" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-stores--id--media-logo">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-stores--id--media-logo" data-method="POST"
      data-path="api/stores/{id}/media/logo"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-stores--id--media-logo', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-stores--id--media-logo"
                    onclick="tryItOut('POSTapi-stores--id--media-logo');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-stores--id--media-logo"
                    onclick="cancelTryOut('POSTapi-stores--id--media-logo');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-stores--id--media-logo"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/stores/{id}/media/logo</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-stores--id--media-logo"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-stores--id--media-logo"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-stores--id--media-logo"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the store. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-stores--id--media-logo"
               value=""
               data-component="body">
    <br>
<p>Must be a file. validation.max. Example: <code>C:\Users\Ing Angel\AppData\Local\Temp\phpDF97.tmp</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-stores--id--media-banner">Upload store banner.</h2>

<p>
</p>

<p>POST /api/stores/{storeId}/media/banner</p>

<span id="example-requests-POSTapi-stores--id--media-banner">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/stores/consequatur/media/banner" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@C:\Users\Ing Angel\AppData\Local\Temp\phpDFA9.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores/consequatur/media/banner"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-stores--id--media-banner">
</span>
<span id="execution-results-POSTapi-stores--id--media-banner" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-stores--id--media-banner"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-stores--id--media-banner"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-stores--id--media-banner" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-stores--id--media-banner">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-stores--id--media-banner" data-method="POST"
      data-path="api/stores/{id}/media/banner"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-stores--id--media-banner', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-stores--id--media-banner"
                    onclick="tryItOut('POSTapi-stores--id--media-banner');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-stores--id--media-banner"
                    onclick="cancelTryOut('POSTapi-stores--id--media-banner');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-stores--id--media-banner"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/stores/{id}/media/banner</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-stores--id--media-banner"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-stores--id--media-banner"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-stores--id--media-banner"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the store. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-stores--id--media-banner"
               value=""
               data-component="body">
    <br>
<p>Must be a file. validation.max. Example: <code>C:\Users\Ing Angel\AppData\Local\Temp\phpDFA9.tmp</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-stores--id--media--mediaId-">Delete store media.</h2>

<p>
</p>

<p>DELETE /api/stores/{storeId}/media/{mediaId}</p>

<span id="example-requests-DELETEapi-stores--id--media--mediaId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/stores/consequatur/media/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/stores/consequatur/media/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-stores--id--media--mediaId-">
</span>
<span id="execution-results-DELETEapi-stores--id--media--mediaId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-stores--id--media--mediaId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-stores--id--media--mediaId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-stores--id--media--mediaId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-stores--id--media--mediaId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-stores--id--media--mediaId-" data-method="DELETE"
      data-path="api/stores/{id}/media/{mediaId}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-stores--id--media--mediaId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-stores--id--media--mediaId-"
                    onclick="tryItOut('DELETEapi-stores--id--media--mediaId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-stores--id--media--mediaId-"
                    onclick="cancelTryOut('DELETEapi-stores--id--media--mediaId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-stores--id--media--mediaId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/stores/{id}/media/{mediaId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-stores--id--media--mediaId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-stores--id--media--mediaId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-stores--id--media--mediaId-"
               value="consequatur"
               data-component="url">
    <br>
<p>The ID of the store. Example: <code>consequatur</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>mediaId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mediaId"                data-endpoint="DELETEapi-stores--id--media--mediaId-"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-products">POST /api/products</h2>

<p>
</p>



<span id="example-requests-POSTapi-products">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/products" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"slug\": \"amniihfqcoynlazghdtqt\",
    \"type\": \"digital\",
    \"sku\": \"qxbajwbpilpmufinllwlo\",
    \"description\": \"Et a qui ducimus.\",
    \"shortDescription\": \"smsjuryvojcybzvrbyick\",
    \"price\": 88,
    \"regularPrice\": 48,
    \"salePrice\": 36,
    \"stock\": 87,
    \"category\": \"consequatur\",
    \"categories\": [
        17
    ],
    \"image\": \"consequatur\",
    \"weight\": 45,
    \"dimensions\": \"qeopfuudtdsufvyvddqam\",
    \"sticker\": \"niihfqcoynlazghdtqtqx\",
    \"discountPercentage\": 2,
    \"mainAttributes\": [
        {
            \"values\": [
                \"consequatur\"
            ]
        }
    ],
    \"additionalAttributes\": [
        {
            \"values\": [
                \"consequatur\"
            ]
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "slug": "amniihfqcoynlazghdtqt",
    "type": "digital",
    "sku": "qxbajwbpilpmufinllwlo",
    "description": "Et a qui ducimus.",
    "shortDescription": "smsjuryvojcybzvrbyick",
    "price": 88,
    "regularPrice": 48,
    "salePrice": 36,
    "stock": 87,
    "category": "consequatur",
    "categories": [
        17
    ],
    "image": "consequatur",
    "weight": 45,
    "dimensions": "qeopfuudtdsufvyvddqam",
    "sticker": "niihfqcoynlazghdtqtqx",
    "discountPercentage": 2,
    "mainAttributes": [
        {
            "values": [
                "consequatur"
            ]
        }
    ],
    "additionalAttributes": [
        {
            "values": [
                "consequatur"
            ]
        }
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-products">
</span>
<span id="execution-results-POSTapi-products" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-products"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-products"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-products" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-products">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-products" data-method="POST"
      data-path="api/products"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-products', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-products"
                    onclick="tryItOut('POSTapi-products');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-products"
                    onclick="cancelTryOut('POSTapi-products');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-products"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/products</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-products"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-products"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-products"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="POSTapi-products"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>validation.max. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-products"
               value="digital"
               data-component="body">
    <br>
<p>Example: <code>digital</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>physical</code></li> <li><code>digital</code></li> <li><code>service</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sku</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sku"                data-endpoint="POSTapi-products"
               value="qxbajwbpilpmufinllwlo"
               data-component="body">
    <br>
<p>validation.max. Example: <code>qxbajwbpilpmufinllwlo</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-products"
               value="Et a qui ducimus."
               data-component="body">
    <br>
<p>validation.max. Example: <code>Et a qui ducimus.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shortDescription</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shortDescription"                data-endpoint="POSTapi-products"
               value="smsjuryvojcybzvrbyick"
               data-component="body">
    <br>
<p>validation.max. Example: <code>smsjuryvojcybzvrbyick</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="POSTapi-products"
               value="88"
               data-component="body">
    <br>
<p>validation.min. Example: <code>88</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>regularPrice</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="regularPrice"                data-endpoint="POSTapi-products"
               value="48"
               data-component="body">
    <br>
<p>validation.min. Example: <code>48</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>salePrice</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="salePrice"                data-endpoint="POSTapi-products"
               value="36"
               data-component="body">
    <br>
<p>validation.min. Example: <code>36</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>stock</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="stock"                data-endpoint="POSTapi-products"
               value="87"
               data-component="body">
    <br>
<p>validation.min. Example: <code>87</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category"                data-endpoint="POSTapi-products"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>categories</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="categories[0]"                data-endpoint="POSTapi-products"
               data-component="body">
        <input type="number" style="display: none"
               name="categories[1]"                data-endpoint="POSTapi-products"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the categories table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="image"                data-endpoint="POSTapi-products"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>weight</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="weight"                data-endpoint="POSTapi-products"
               value="45"
               data-component="body">
    <br>
<p>validation.min. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dimensions</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dimensions"                data-endpoint="POSTapi-products"
               value="qeopfuudtdsufvyvddqam"
               data-component="body">
    <br>
<p>validation.max. Example: <code>qeopfuudtdsufvyvddqam</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sticker</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sticker"                data-endpoint="POSTapi-products"
               value="niihfqcoynlazghdtqtqx"
               data-component="body">
    <br>
<p>validation.max. Example: <code>niihfqcoynlazghdtqtqx</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>discountPercentage</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="discountPercentage"                data-endpoint="POSTapi-products"
               value="2"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>mainAttributes</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>values</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mainAttributes.0.values[0]"                data-endpoint="POSTapi-products"
               data-component="body">
        <input type="text" style="display: none"
               name="mainAttributes.0.values[1]"                data-endpoint="POSTapi-products"
               data-component="body">
    <br>

                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>additionalAttributes</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>values</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="additionalAttributes.0.values[0]"                data-endpoint="POSTapi-products"
               data-component="body">
        <input type="text" style="display: none"
               name="additionalAttributes.0.values[1]"                data-endpoint="POSTapi-products"
               data-component="body">
    <br>

                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-products--id-">PUT /api/products/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-products--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/products/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"vmqeopfuudtdsufvyvddq\",
    \"slug\": \"amniihfqcoynlazghdtqt\",
    \"type\": \"service\",
    \"sku\": \"qxbajwbpilpmufinllwlo\",
    \"description\": \"Et a qui ducimus.\",
    \"shortDescription\": \"smsjuryvojcybzvrbyick\",
    \"price\": 88,
    \"regularPrice\": 48,
    \"salePrice\": 36,
    \"stock\": 87,
    \"category\": \"consequatur\",
    \"categories\": [
        17
    ],
    \"image\": \"consequatur\",
    \"weight\": 45,
    \"dimensions\": \"qeopfuudtdsufvyvddqam\",
    \"sticker\": \"niihfqcoynlazghdtqtqx\",
    \"discountPercentage\": 2,
    \"mainAttributes\": [
        {
            \"values\": [
                \"consequatur\"
            ]
        }
    ],
    \"additionalAttributes\": [
        {
            \"values\": [
                \"consequatur\"
            ]
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "vmqeopfuudtdsufvyvddq",
    "slug": "amniihfqcoynlazghdtqt",
    "type": "service",
    "sku": "qxbajwbpilpmufinllwlo",
    "description": "Et a qui ducimus.",
    "shortDescription": "smsjuryvojcybzvrbyick",
    "price": 88,
    "regularPrice": 48,
    "salePrice": 36,
    "stock": 87,
    "category": "consequatur",
    "categories": [
        17
    ],
    "image": "consequatur",
    "weight": 45,
    "dimensions": "qeopfuudtdsufvyvddqam",
    "sticker": "niihfqcoynlazghdtqtqx",
    "discountPercentage": 2,
    "mainAttributes": [
        {
            "values": [
                "consequatur"
            ]
        }
    ],
    "additionalAttributes": [
        {
            "values": [
                "consequatur"
            ]
        }
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-products--id-">
</span>
<span id="execution-results-PUTapi-products--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-products--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-products--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-products--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-products--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-products--id-" data-method="PUT"
      data-path="api/products/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-products--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-products--id-"
                    onclick="tryItOut('PUTapi-products--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-products--id-"
                    onclick="cancelTryOut('PUTapi-products--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-products--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/products/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-products--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-products--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-products--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-products--id-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="PUTapi-products--id-"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>validation.max. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PUTapi-products--id-"
               value="service"
               data-component="body">
    <br>
<p>Example: <code>service</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>physical</code></li> <li><code>digital</code></li> <li><code>service</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sku</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sku"                data-endpoint="PUTapi-products--id-"
               value="qxbajwbpilpmufinllwlo"
               data-component="body">
    <br>
<p>validation.max. Example: <code>qxbajwbpilpmufinllwlo</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-products--id-"
               value="Et a qui ducimus."
               data-component="body">
    <br>
<p>validation.max. Example: <code>Et a qui ducimus.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shortDescription</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shortDescription"                data-endpoint="PUTapi-products--id-"
               value="smsjuryvojcybzvrbyick"
               data-component="body">
    <br>
<p>validation.max. Example: <code>smsjuryvojcybzvrbyick</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="PUTapi-products--id-"
               value="88"
               data-component="body">
    <br>
<p>validation.min. Example: <code>88</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>regularPrice</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="regularPrice"                data-endpoint="PUTapi-products--id-"
               value="48"
               data-component="body">
    <br>
<p>validation.min. Example: <code>48</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>salePrice</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="salePrice"                data-endpoint="PUTapi-products--id-"
               value="36"
               data-component="body">
    <br>
<p>validation.min. Example: <code>36</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>stock</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="stock"                data-endpoint="PUTapi-products--id-"
               value="87"
               data-component="body">
    <br>
<p>validation.min. Example: <code>87</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category"                data-endpoint="PUTapi-products--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>categories</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="categories[0]"                data-endpoint="PUTapi-products--id-"
               data-component="body">
        <input type="number" style="display: none"
               name="categories[1]"                data-endpoint="PUTapi-products--id-"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the categories table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="image"                data-endpoint="PUTapi-products--id-"
               value="consequatur"
               data-component="body">
    <br>
<p>Example: <code>consequatur</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>weight</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="weight"                data-endpoint="PUTapi-products--id-"
               value="45"
               data-component="body">
    <br>
<p>validation.min. Example: <code>45</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dimensions</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dimensions"                data-endpoint="PUTapi-products--id-"
               value="qeopfuudtdsufvyvddqam"
               data-component="body">
    <br>
<p>validation.max. Example: <code>qeopfuudtdsufvyvddqam</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sticker</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sticker"                data-endpoint="PUTapi-products--id-"
               value="niihfqcoynlazghdtqtqx"
               data-component="body">
    <br>
<p>validation.max. Example: <code>niihfqcoynlazghdtqtqx</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>discountPercentage</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="discountPercentage"                data-endpoint="PUTapi-products--id-"
               value="2"
               data-component="body">
    <br>
<p>validation.min validation.max. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>mainAttributes</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>values</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mainAttributes.0.values[0]"                data-endpoint="PUTapi-products--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="mainAttributes.0.values[1]"                data-endpoint="PUTapi-products--id-"
               data-component="body">
    <br>

                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>additionalAttributes</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>values</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="additionalAttributes.0.values[0]"                data-endpoint="PUTapi-products--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="additionalAttributes.0.values[1]"                data-endpoint="PUTapi-products--id-"
               data-component="body">
    <br>

                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-products--id-">DELETE /api/products/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-products--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/products/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-products--id-">
</span>
<span id="execution-results-DELETEapi-products--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-products--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-products--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-products--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-products--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-products--id-" data-method="DELETE"
      data-path="api/products/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-products--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-products--id-"
                    onclick="tryItOut('DELETEapi-products--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-products--id-"
                    onclick="cancelTryOut('DELETEapi-products--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-products--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/products/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-products--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-products--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-products--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-products--id--stock">PUT /api/products/{id}/stock</h2>

<p>
</p>



<span id="example-requests-PUTapi-products--id--stock">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/products/1/stock" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"quantity\": 73
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1/stock"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "quantity": 73
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-products--id--stock">
</span>
<span id="execution-results-PUTapi-products--id--stock" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-products--id--stock"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-products--id--stock"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-products--id--stock" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-products--id--stock">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-products--id--stock" data-method="PUT"
      data-path="api/products/{id}/stock"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-products--id--stock', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-products--id--stock"
                    onclick="tryItOut('PUTapi-products--id--stock');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-products--id--stock"
                    onclick="cancelTryOut('PUTapi-products--id--stock');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-products--id--stock"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/products/{id}/stock</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-products--id--stock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-products--id--stock"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-products--id--stock"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="quantity"                data-endpoint="PUTapi-products--id--stock"
               value="73"
               data-component="body">
    <br>
<p>validation.min. Example: <code>73</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-products--id--media">Get product media.</h2>

<p>
</p>

<p>GET /api/products/{productId}/media</p>

<span id="example-requests-GETapi-products--id--media">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/products/1/media" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1/media"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-products--id--media">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: http://localhost:3000
access-control-allow-credentials: true
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-products--id--media" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-products--id--media"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-products--id--media"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-products--id--media" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-products--id--media">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-products--id--media" data-method="GET"
      data-path="api/products/{id}/media"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-products--id--media', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-products--id--media"
                    onclick="tryItOut('GETapi-products--id--media');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-products--id--media"
                    onclick="cancelTryOut('GETapi-products--id--media');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-products--id--media"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/products/{id}/media</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-products--id--media"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-products--id--media"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-products--id--media"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-products--id--media">Upload media to a product.</h2>

<p>
</p>

<p>POST /api/products/{productId}/media</p>

<span id="example-requests-POSTapi-products--id--media">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/products/1/media" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@C:\Users\Ing Angel\AppData\Local\Temp\phpDFE9.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1/media"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-products--id--media">
</span>
<span id="execution-results-POSTapi-products--id--media" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-products--id--media"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-products--id--media"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-products--id--media" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-products--id--media">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-products--id--media" data-method="POST"
      data-path="api/products/{id}/media"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-products--id--media', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-products--id--media"
                    onclick="tryItOut('POSTapi-products--id--media');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-products--id--media"
                    onclick="cancelTryOut('POSTapi-products--id--media');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-products--id--media"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/products/{id}/media</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-products--id--media"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-products--id--media"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-products--id--media"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-products--id--media"
               value=""
               data-component="body">
    <br>
<p>Must be a file. validation.max. Example: <code>C:\Users\Ing Angel\AppData\Local\Temp\phpDFE9.tmp</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-products--id--media--mediaId-">Delete product media.</h2>

<p>
</p>

<p>DELETE /api/products/{productId}/media/{mediaId}</p>

<span id="example-requests-DELETEapi-products--id--media--mediaId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/products/1/media/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1/media/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-products--id--media--mediaId-">
</span>
<span id="execution-results-DELETEapi-products--id--media--mediaId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-products--id--media--mediaId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-products--id--media--mediaId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-products--id--media--mediaId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-products--id--media--mediaId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-products--id--media--mediaId-" data-method="DELETE"
      data-path="api/products/{id}/media/{mediaId}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-products--id--media--mediaId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-products--id--media--mediaId-"
                    onclick="tryItOut('DELETEapi-products--id--media--mediaId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-products--id--media--mediaId-"
                    onclick="cancelTryOut('DELETEapi-products--id--media--mediaId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-products--id--media--mediaId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/products/{id}/media/{mediaId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-products--id--media--mediaId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-products--id--media--mediaId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-products--id--media--mediaId-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>mediaId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="mediaId"                data-endpoint="DELETEapi-products--id--media--mediaId-"
               value="consequatur"
               data-component="url">
    <br>
<p>Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-products--id--media-reorder">Reorder product media.</h2>

<p>
</p>

<p>PUT /api/products/{productId}/media/reorder</p>

<span id="example-requests-PUTapi-products--id--media-reorder">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/products/1/media/reorder" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/products/1/media/reorder"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PUT",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-products--id--media-reorder">
</span>
<span id="execution-results-PUTapi-products--id--media-reorder" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-products--id--media-reorder"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-products--id--media-reorder"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-products--id--media-reorder" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-products--id--media-reorder">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-products--id--media-reorder" data-method="PUT"
      data-path="api/products/{id}/media/reorder"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-products--id--media-reorder', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-products--id--media-reorder"
                    onclick="tryItOut('PUTapi-products--id--media-reorder');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-products--id--media-reorder"
                    onclick="cancelTryOut('PUTapi-products--id--media-reorder');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-products--id--media-reorder"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/products/{id}/media/reorder</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-products--id--media-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-products--id--media-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-products--id--media-reorder"
               value="1"
               data-component="url">
    <br>
<p>The ID of the product. Example: <code>1</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
