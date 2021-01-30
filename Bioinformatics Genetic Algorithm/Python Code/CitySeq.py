# -*- coding: utf-8 -*-
"""
Created on Tue Oct  6 13:32:25 2020

@author: lidou
"""

class CitySeq:
    
    sequence = []
    distance = 0

#constructor
    def __init__(self, seq):
        self.sequence = seq
#end of constructor
        
#calculate the distance
    def calDistance(self, distArr):
        dist = 0
        for x in range(7):
            dist = dist+distArr[self.sequence[x]][self.sequence[x+1]]
            
            #print(dist)
        self.distance= dist
#end of calculate the distance        

    def getSeq(self):
        #print(self.sequence)
        return self.sequence

    def getDistance(self):
        return self.distance
    
    def swapSeq(self, numA, numB):
        temp = self.sequence[numA]
        self.sequence[numA] = self.sequence[numB]
        self.sequence[numB] = temp