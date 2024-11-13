# Frontend User Role and Post Viewing Restrictions

I have implemented a custom user role called **`frontend_user`** in the WordPress website. This role has specific capabilities and restrictions, which are described below:

## 1. **Frontend User Role Creation**
- A new user role, **`frontend_user`**, has been created. This role has limited access to the website.
- The **admin** has the ability to assign this role to a user via the user profile page.
- When the **admin** selects this role, the user will have access to a list of posts that the admin specifically allows.

## 2. **Post Selection by Admin**
- The **admin** can select specific posts that **`frontend_user`** can view.
- These posts are restricted to the user, meaning they will only be able to view them if they are logged in as a `frontend_user`.
- The posts list is visible to the admin while updating the user profile.

## 3. **Login Restrictions**
- The **`frontend_user`** is restricted to login only **4 times** in total.
- After 4 successful logins, the user will no longer be able to log in to the website.

## 4. **Auto Logout After 5 Minutes of Inactivity**
- To enhance security, if the **`frontend_user`** remains inactive for more than **5 minutes**, they will automatically be logged out of the website.
- This feature ensures that users don't stay logged in for longer than necessary, preventing unauthorized access after prolonged inactivity.

## 5. **How It Works**
- **Admin** assigns the **`frontend_user`** role and selects posts for the user in the user profile page.
- The **`frontend_user`** must log in to view the selected posts.
- The user can only view the posts that have been selected by the admin.
- If the user logs in 4 times or exceeds the 5-minute inactivity period, they will not be able to access the site.

## Summary
This functionality allows **admins** to control which content is visible to the **`frontend_user`** role while maintaining security with login restrictions and inactivity logout mechanisms. It’s an effective way to limit access to certain content while ensuring users don’t stay logged in longer than needed.
