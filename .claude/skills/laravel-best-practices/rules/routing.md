# Routing & Controllers Best Practices

## Use Implicit Route Model Binding

Let Laravel resolve models automatically from route parameters.

Incorrect:
```php
public function show(int $id)
{
    $post = Post::findOrFail($id);
}
```

Correct:
```php
public function show(Post $post)
{
    return view('posts.show', ['post' => $post]);
}
```

## Use Scoped Bindings for Nested Resources

Enforce parent-child relationships automatically.

```php
Route::get('/users/{user}/posts/{post}', function (User $user, Post $post) {
    // $post is automatically scoped to $user
})->scopeBindings();
```

## Use Resource Controllers

Use `Route::resource()` or `apiResource()` for RESTful endpoints.

```php
Route::resource('posts', PostController::class);
// In routes/api.php — the /api prefix is applied automatically
Route::apiResource('posts', Api\PostController::class);
```

## Use CRUD Method Names Only

Controllers must expose standard REST verbs — `index`, `create`, `store`, `show`, `edit`, `update`, and `destroy` — not bespoke action names.

Incorrect:
```php
Route::post('uploads/signed-url', [UploadController::class, 'signedUrl']);

public function signedUrl(SignedUploadUrlRequest $request): JsonResponse
{
    // ...
}
```

Correct:
```php
Route::resource('uploads', UploadController::class)->only(['store']);

public function store(StoreUploadRequest $request): JsonResponse
{
    // ...
}
```

Limit resource routes with `only()` / `except()` when you do not need every action. Name Form Requests after the verb (`StoreUploadRequest`, `UpdatePostRequest`, etc.).

## Keep Controllers Thin

Aim for under 10 lines per method. Extract business logic to action or service classes.

Incorrect:
```php
public function store(Request $request)
{
    $validated = $request->validate([...]);
    if ($request->hasFile('image')) {
        $request->file('image')->move(public_path('images'));
    }
    $post = Post::create($validated);
    $post->tags()->sync($validated['tags']);
    event(new PostCreated($post));
    return redirect()->route('posts.show', $post);
}
```

Correct:
```php
public function store(StorePostRequest $request, CreatePost $createPost)
{
    $post = $createPost->execute($request->validated());

    return redirect()->route('posts.show', $post);
}
```

Place `CreatePost` and similar classes in `app/Actions/{Domain}/` with an `execute()` method.

## Type-Hint Form Requests

Type-hinting Form Requests triggers automatic validation and authorization before the method executes.

Incorrect:
```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'title' => ['required', 'max:255'],
        'body' => ['required'],
    ]);

    Post::create($validated);

    return redirect()->route('posts.index');
}
```

Correct:
```php
public function store(StorePostRequest $request): RedirectResponse
{
    Post::create($request->validated());

    return redirect()->route('posts.index');
}
```
