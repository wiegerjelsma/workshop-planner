# A PHP workshop planner

We have workshops and attendees but limited conference rooms and time slots.

---

# The challenge

- Every attendee can choose 3 from 15 workshops. 
- We have 6 rooms available over 4 time slots.
- Workshops can be given more than once, but not twice at the same time. 
- And last but not least; An attandee can only be in one room at the time.

We don't know how many attendees eventually will sign up for workshops.

# The approach

First I created a random set of attendees with 3 random choosen workshops.
Then I sorted the workshops on popularity and placed them after each other over the timeslots. Assign each attendee to their workshops and see which timeslot gives us the most conflicts. Solve those conflicts by switching the workshop to another timeslot if possible. Find the next workshop with the most conflicts and try to solve those as well. Repeat this process per workshop until we cannot solve any conflicts anymore.

It is not possible to assign every attendee to their three workshops of choice, but we've tried to come up with the solution with the least conflicts.


# Usage

Just browse to `index.php` in a webbrowser. The webserver will execute the script and serve the output on screen.
To use this in a real life example the script needs input for attendees and choosen workshops.




