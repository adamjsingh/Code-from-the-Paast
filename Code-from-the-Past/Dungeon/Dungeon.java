import java.io.*;
import java.util.*;
import java.lang.*;

//Dungeon class
public class Dungeon
{
    protected int numRooms = 0; //Total number of rooms in the Dungeon
    protected List<Room> rooms = new ArrayList<Room>(); //List of Room objects
    
    //Main Function
    public static void main(String[] args)throws IOException
    {
        boolean badRead;
        BufferedReader bin = new BufferedReader(new InputStreamReader(System.in));
        int num;
        do
        {
            badRead = false;
            num = 0;
            System.out.println("Please enter the number of rooms for the dungeon.");
            System.out.println("The number of rooms must be greater than or equal to 2.");
            String number = bin.readLine();
            
            try
            { 
                num = Integer.parseInt(number);
            }
            
            catch(NumberFormatException e)
            {
                badRead = true;
            }
            
            if(!badRead && num < 2)
            {
                badRead = true;
            }
        }while(badRead);
        
        Dungeon dungeon = new Dungeon(num);
        dungeon.printDungeon();
    }
    
    //Constructor to generate the dungeon
    public Dungeon (int num)
    {
        if(num < 2)
            System.out.println("There are too few rooms.");
            
        else
        {
            Room start = new Room("Start");
            Room exit = new Room("Exit");
            
            if(!this.addRoom(start) || !this.addRoom(exit))
                System.out.println("Dungeon failed to create.");
                
            else
            {
                System.out.println("Start and Exit rooms created and added.");
                
                //After the start and exit rooms are created, the rest of the rooms
                //are given a random type and sequenchal number.
                String[] types = {"monster", "trap", "treasure", "empty"};
                Random die = new Random();
                String rName;
                
                //Adds a number of random room that is two less than the indicated value.
                for(int i = 0; i < num-2; i++)
                {
                    //Rooms are added one at a time to the list.
                    rName = types[die.nextInt(4)];
                    Room temp = new Room(rName+" "+i);
                    if(!this.addRoom(temp))
                        System.out.println("Could not add "+rName+" "+i+".");
                        
                    else
                        System.out.println(rName+" "+i+" has been added.");
                }
                
                //Randomly attaching the rooms
                int dir;
                int select;
                int[] picked = new int[numRooms];
                int pSize = 0;
                String[] directions = {"above", "below", "north",
                                        "south", "east", "west"};
                
                //Loop through each of the rooms in the list.
                for(int i=0; i < numRooms; i++)
                {
                    //The first room is simply put in the picked array.
                    //Picked array's size is increased.
                    if(i==0)
                    {
                        picked[0] = 0;
                        pSize = 1;
                    }
                   
                    //With the rest of the rooms, a random picked room and direction are selected
                    //until there is one that is free.  That room is attached to the current room at
                    //the indicated direction. The current room is move to the picked pile.
                    //Picked array's size is increased.
                    else
                    {
                        do
                        {
                            select = die.nextInt(pSize);
                            dir = die.nextInt(6);
                        }while(rooms.get(select).isAttached(directions[dir]));
                        
                        rooms.get(select).setAdjacent(rooms.get(i), directions[dir]);
                        picked[i] = i;
                        pSize++;
                    }
                }
            }
        }
    }
    
    //Return the number of rooms in Dungeon
    public int getRooms()
    {
        return numRooms;
    }
    
    public Room getRoom(int i)
    {
        return rooms.get(i);
    }
    
    //Function to add a room to the duneon 
    public boolean addRoom(Room r)
    {
        //Check to see if the room does not exist
        //So each room is unique.
        if(!findRoom(r)) 
        {
            rooms.add(r); //Add room to the list of Rooms
            numRooms++; //Increase the number of total rooms
            sort(); // Sort room List
            return true;
        }
        
        else  //If room does exist, don't add and return false
            return false;
    }
    
    //Function that finds a room in the list of sorted rooms in the dungeon
    //Uses a binary searth tree to to have search be O(n/2)
    public boolean findRoom(Room r)
    {
        int start = 0;
        int end = numRooms-1;
        
        while (start <= end)
        {
            int mid = (start + end)/2;
            
            if(rooms.get(mid).getName().compareTo(r.getName()) == 0)
                return true;
                
            else if(r.getName().compareTo(rooms.get(mid).getName()) < 0)
                end = mid -1 ;
            
            else
                start = mid + 1;
        }
        
        return false;
    }
    
    //Function that finds the index of a named room in the dungeon
    //Uses a binary searth tree to to have search be O(n/2)
    public int findIndex(String room)
    {
        int start = 0;
        int end = numRooms-1;
        
        while (start <= end)
        {
            int mid = (start + end)/2;
            
            if(rooms.get(mid).getName().compareTo(room) == 0)
                return mid;
                
            else if(room.compareTo(rooms.get(mid).getName()) < 0)
                end = mid -1 ;
            
            else
                start = mid + 1;
        }
        
        return -1;
    }
    
    //Delete function
    //Uses Room's disconnect function and removes the room.
    //O(n/2) using findIndex
    public boolean delRoom(String room)
    {
        int index = findIndex(room);
        
        if(index == -1)
            return false;
        
        else
        {
            rooms.get(index).disconnect();
            rooms.remove(index);
            return true;
        }
    }
    
    // This is a a reverse bubble sort.
    // This is used since the list is sorted with every addition of
    // a room to the list of rooms in the dungeon.  O(n/2)
    // If there would be several rooms added to the list at a time
    //  Then I would have used quick sort.
    public void sort()
    {
        if(numRooms > 1)
        {
            int curr = numRooms - 1;
            
            while(curr > 0 && rooms.get(curr).getName().compareTo(rooms.get(curr-1).getName()) < 0)
            {
                Room temp = rooms.get(curr);
                rooms.set(curr, rooms.get(curr-1));
                rooms.set(curr-1, temp);
                curr--;
            }
        }
    }
    
    //Prints the rooms in the dungeon and
    //calls printAdjacent to print each rooms
    //adjacent rooms.
    public void printDungeon()
    {
        for(int i = 0; i < numRooms; i++)
        {
            Room temp = rooms.get(i);
            System.out.println("Room Name: " + temp.getName());
            temp.printAdjacent();
        }
    }
}