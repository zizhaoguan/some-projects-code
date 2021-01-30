# -*- coding: utf-8 -*-
"""
Created on Tue Oct  6 16:22:20 2020

@author: lidou
"""
from CitySeq import*
import numpy as np
import random

class Population:
    
    individuals = [] #list of individual
    averageScore = 0   #average distance
    medianScore = 0 # the median score
    STDScores = [] # the standard scores
    selected = 0 # size of population that is pass the selection
    
#constructor
    def __init__(self):
        self.individuals = []
        self.averageScore = 0
        self.medianScore = 0
        self.STDScores = []
        self.selected = 0
#end of constructor    

#adding single city sequence to the set of individuals to make the population
    def addIndividuals(self, aCitySeq):
        self.individuals.append([aCitySeq, aCitySeq.getDistance()])
       
#end of the method

     
    def setSelected(self, size):
        self.selected = size
    
    def getSelected(self):
        return self.selected
        
    def calAverage(self):
        sum = 0
        for x in self.individuals:
            sum += x[1]
        self.averageScore = sum/len(self.individuals)
    
    def getAverage(self):
        return self.averageScore
    
    def sortPopulation(self):
        self.individuals = sorted(self.individuals, key=lambda aCitySeq: aCitySeq[1], reverse=False)
    
    
    def calMedianScore(self):
        self.sortPopulation()
        self.medianScore = self.individuals[round(len(self.individuals)/2)][1]
      
        
    def getMedianScore(self):
        return self.medianScore
    
    def getPopulation(self):
        '''
        for x in self.individuals:
            print(x[0].getSeq())
        '''
        return self.individuals
        
    def printPopulation(self):
        for x in self.individuals:
            print('individual obj: ', x,
                  '\nsequence: ', x[0].getSeq(),'\ndistance:', x[1])

    def calSTDScores(self):
        scores = []
        self.STDScores = []
        for x in self.individuals:
            scores.append(x[1])
        scores_mean = np.mean(scores)
        #scores_var = np.var(scores)
        scores_std = np.std(scores)
        
        for x in scores:
            #print("std: ", scores_std)
            self.STDScores.append((x-scores_mean)/scores_std)
        
        
    def getSTDScores(self):
        return self.STDScores
    
    def calAllScores(self):
        self.calAverage()
        self.calMedianScore()
        self.calSTDScores()
    
    def swapIndividualSeq(self, num, distanceArr):
        print("in class: \nbefore swap: ", self.individuals[num][0].getSeq())
        print("in class: \nbefore swap distance = ", self.individuals[num][0].getDistance())
        
        randomNumA = random.randint(0, 7)
        randomNumB = random.randint(0, 7)
        print("swap between:",randomNumA, " and ", randomNumB)
        
        self.individuals[num][0].swapSeq(randomNumA, randomNumB)
        self.individuals[num][0].calDistance(distanceArr)
        self.individuals[num][1] = self.individuals[num][0].getDistance()
        print("in class: \nafter swap:", self.individuals[num][0].getSeq())
        print("in class: \nafter swap distance = ", self.individuals[num][0].getDistance(), "\nthe scores in the list:", self.individuals[num][1])
        