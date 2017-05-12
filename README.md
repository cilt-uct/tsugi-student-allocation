TSUGI - A student allocation tool
=============================================================

An LTI tool written in Tsugi which handles student project allocation.

Back-end project allocation is done by
https://github.com/richarddmorey/studentProjectAllocation

Sakai integration scripts at:
http://source.cet.uct.ac.za/svn/sakai/scripts/trunk/groupallocation/

UCT reference:
--------------
https://jira.cet.uct.ac.za/browse/VULA-2971

Pre-Requisites
--------------

* Tsugi

Dependencies
------------
* perl

To install the perl module List::MoreUtils (may need to run this as root with sudo):

```
cpan
install List::MoreUtils
```

Sample text files for input to studentProjectAllocation
=======================================================

lecturers.txt (we use a single dummy lecturer with up to 1000 students)
```
lecturer 1000
```

students.txt (student number followed by group preference in descending order of rank, i.e. 1st choice, 2nd choice, etc.)
```
abcxyz001 p34 p8 p134 p26 p1 p68 p40 p39 p93 p35 
abczyz002 p38 p65 p99 p85 p23 p26 p50 p100 p32 p34 
abcxyz003 p119 p38 p107 p123 p32 p67 p66 p70 p39 p49 
```

projects.txt (project identifier followed by maximum size followed by lecturer id which here is just 'lecturer')
```
p113 2 lecturer
p30 3 lecturer
p102 2 lecturer
p69 1 lecturer
p107 4 lecturer
p125 2 lecturer
p67 2 lecturer
p84 4 lecturer
p64 2 lecturer
p60 5 lecturer
p38 12 lecturer
p92 3 lecturer
```
