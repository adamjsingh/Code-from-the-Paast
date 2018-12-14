import java.io.*;
import java.util.*;
import java.lang.*;

// Room class
public class Room
{
    //I thought of using a hash tables for
    //the adjacent references.  The reason why
    //I didn't is because Java creates enumerations
    //to access keys and values in a Hashtable and
    //I wanted to save space and garbage collection time.
    protected String name;
    protected Room above;
    protected Room below;
    protected Room north;
    protected Room south;
    protected Room east;
    protected Room west;
    
    //Room constructor
    public Room (String n)
    {
        name = n;
        above = null;
        below = null;
        north = null;
        south = null;
        east = null;
        west = null;
    }
    
    //Checks to see if the current room is adjacent to the other room
    //and connected in the right direction.
    public boolean isAdjacent(Room other)
    {
        if(this.above.getName() == other.getName() && other.below.getName() == this.getName())
            return true;
            
        else if(this.below.getName() == other.getName() && other.above.getName() == this.getName())
            return true;
            
        else if(this.north.getName() == other.getName() && other.south.getName() == this.getName())
            return true;
            
        else if(this.south.getName() == other.getName() && other.north.getName() == this.getName())
            return true;
            
        else if(this.east.getName() == other.getName() && other.west.getName() == this.getName())
            return true;
            
        else if(this.west.getName() == other.getName() && other.east.getName() == this.getName())
            return true;
            
        else
            return false;
        
    }
    
    //Sets other room adjacent to this room in the direction specified.
    public void setAdjacent(Room other, String direction)
    {
        if(direction == "above")
        {
            this.above = other;
            other.below = this;
        }
        
        else if(direction == "below")
        {
            this.below = other;
            other.above = this;
        }
        
        else if(direction == "north")
        {
            this.north = other;
            other.south = this;
        }
        
        if(direction == "south")
        {
            this.south = other;
            other.north = this;
        }
        
        if(direction == "east")
        {
            this.east = other;
            other.west = this;
        }
        
        if(direction == "west")
        {
            this.west = other;
            other.east = this;
        }
    }
    
    //This function is used to notify the programmer
    //If the room is adjacent to a room in the specified
    //direction.
    public boolean isAttached(String direction)
    {
        if(direction == "above")
            return above != null;
        
        else if(direction == "below")
            return below != null;
        
        else if(direction == "north")
            return north != null;
        
        else if(direction == "south")
            return south != null;
        
        else if(direction == "east")
            return east != null;
        
        else if(direction == "west")
            return west != null;
        
        else
            return false;
    }
    
    public String getName()
    {
        return name;
    }
    
    //Printing all rooms that are adjacent to the current room.
    public void printAdjacent()
    {
        if(north != null)
            System.out.println("North: " + north.getName());
            
        if(south != null)
            System.out.println("South: " + south.getName());
        
        if(east != null)   
            System.out.println("East: " + east.getName());
            
        if(west != null)
            System.out.println("West: " + west.getName());
            
        if(above != null)
            System.out.println("Above: " + above.getName());
            
        if(below != null)
            System.out.println("Below: " + below.getName());
    }
    
    //The disconnect function is used to disconnect a room
    //from it's adjacent rooms before it is deleted.
    public void disconnect()
    {
        if(north != null)
        {
            north.south = null;
            north = null;
        }
        
        if(south != null)
        {
            south.north = null;
            south = null;
        }
        
        if(east != null)
        {
            east.west = null;
            east = null;
        }
        
        if(west != null)
        {
            west.east = null;
            west = null;
        }
        
        if(above != null)
        {
            above.below = null;
            above = null;
        }
        
        if(below != null)
        {
            below.above = null;
            below = null;
        }
    }
}
