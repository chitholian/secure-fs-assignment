# SecureFS

This is a very simple project and completed as a coding assignment.

## System Requirements

- *Docker* with `compose` plugin.
- Internet connection (for downloading docker images)

## Preparation

- Clone this repository to your PC: `git clone https://github.com/chitholian/secure-fs-assignment.git`
- Go to the cloned directory: `cd secure-fs-assignment`
- Create `.env` file based on `.env.example` file: `cp .env.example .env`
- Update the `.env` file if you wish to tweak things.
- Look inside `contrib/init-db.sql` file. Update initial admin username and password hash if you like.
    - Default username: `admin`
    - Default password: `My-s3cure-Pa55`
- Look inside the `includes/config.php` file, you can update things like download link expiry duration etc.

## Run it

- Go to the root directory of the project.
- Run command `docker compose up`
- It will download necessary docker images, build custom images if necessary and run the system.
- If everything goes well, you can open [http://localhost:8000](http://localhost:8000) in your favourite browser.

## Notes

- As it is a coding assignment, I used raw codes here without using any framework or library.
- It proves my understanding on how things work under the hood.
- For simplicity, I did not end-up creating my own framework or library here.
