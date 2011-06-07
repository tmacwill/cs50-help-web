CS50 Help Web
=============

Web UI for CS50 Help (formerly known as office hours). Ask a question, view your place in line, and chat with friends working on the same thing. Students are dispatched to TFs and CAs via the CS50 Help iPad app.

CS50 Help Web utilizes a REST API. All calls return JSON, and will include a `success` parameter if the operation was successful.

POST /students/add: Add a new student who has a question.
**Parameters**
* `question`: Text of the student's question
* `name`: Name of the student
* `category`: Category the student's question falls into
**Returns**
* `id`: The ID of the newly added student

POST /students/closed: Student has closed his or her window
**Parameters**
* `id`: ID of the student who has closed his or her window

POST /students/dispatch: Student has been dispatched to a TF/CA.
**Parameters**
* `id`: ID of student who has been dispatched
* `tf`: TF/CA student has been dispatched to

POST /students/hand_down: Student has put his or her hand down (and no longer needs assistance).
**Parameters**
* `id`: ID of the student who put his or her hand down

GET /students/queue: Retrieve the current state of the queue.
**Returns**
* `queue`: Ordered array of students, including IDs, question text, category, and timestamp
