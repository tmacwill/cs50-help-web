CS50 Help Web
=============

Web UI for CS50 Help (formerly known as office hours). Ask a question, view your place in line, and chat with friends working on the same thing. Students are dispatched to TFs and CAs via the CS50 Help iPad app.

CS50 Help Web utilizes a REST API. All calls return JSON, and will include a `success` parameter if the operation was successful.

## Routes

### POST /questions/add 
Add a new question.
#### Parameters
* `question`: Text of the student's question
* `name`: Name of the student
* `category`: Category the student's question falls into

#### Returns
* `id`: The ID of the newly added question

***

### POST /questions/closed
Student has closed his or her window.
#### Parameters
* `id`: ID of the question that is no longer active

***

### POST /questions/dispatch
Question has been dispatched to a TF/CA.
#### Parameters
* `id`: ID of question who has been dispatched
* `tf`: TF/CA question has been dispatched to

***

### GET /questions/dispatched
Retrieve a list of dispatched questions. Note: only a student's most recent dispatch will appear.
#### Returns
* `dispatched`: Ordered array of questions, including IDs, question text, show, category, TF, and timestamp

***

### POST /questions/hand_down
Student has put his or her hand down (and no longer needs assistance on the active question).
#### Parameters
* `id`: ID of the question that is no longer active

***

### GET /questions/queue
Retrieve the current state of the queue.
#### Returns
* `queue`: Ordered array of students, including IDs, question text, category, and timestamp
