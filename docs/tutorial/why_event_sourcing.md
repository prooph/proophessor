# Why Event Sourcing?

Event Sourcing is a fascinating topic, but the learning curve is steep. This is not because Event Sourcing is a complex
pattern but because it **conflicts** with our current understanding of **data-centric** object-oriented programming.
This understanding is driven by the way we work with databases, mostly relational databases but
it is not limited to them.

Our primary goal is to put consistent data into a database and keep it consistent. A common way is to normalize the
data, and this is where the problem starts.

**Mapping the real world to a normalized view is a complex task**, and we tend to do it before
we do anything else. Business describes a new feature, and we immediately think of a possible
data structure that fits into our favorite database design. We can share this thinking with our
teammates because every programmer did the same thing in their head already. We can simply match results and are good to go, right?

![Map process to UML](img/process_to_uml.png)

Well, not really. Often, the first draft of a data structure does not work as expected, so we have to reshape it and try again.
And not to forget this shitty real world, no one wants to view normalized data, so we have to write queries and make heavy use of
joins. Then we recognize that fetching the data with joins is inefficient, and we use caches and other techniques to work around
the limitation instead of solving the problem in the first place!

But that's not all. The worst part of this approach is that we design our application logic around data
and not around behaviour.

**We skip the behaviour part of the feature description, turn the feature into our own view of normalized state,
and then reverse engineer behaviour.**

It's not hard to imagine that this approach leads to an application design driven by the database, so it is a
technical design that does not match the original business idea.
If you think this is the best way to tackle software complexity, then you should probably stop reading, because you won't have any
fun with Event Sourcing.

If you cannot get rid of the feeling that there is something inherently wrong with database driven designs, then
you should read on, follow the tutorial, and, slowly but surely, become a software developer focused on real business behaviour.

Event Sourcing has many advantages in modern systems: coordinating work across different services, solving performance
and concurrency problems, and many more.

**But the most important advantage of Event Sourcing is the focus on behaviour.**

![No UML](img/no_uml.png)


