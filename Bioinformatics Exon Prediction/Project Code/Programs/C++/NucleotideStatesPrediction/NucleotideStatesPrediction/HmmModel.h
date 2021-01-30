#ifndef HMMMODEL_H
#define HMMMODEL_H

#include <iostream>
#include <ctime>
#include <fstream>
#include <String>
#include <iomanip>
#include <vector>

using namespace std;

class HmmModel
{
	int N;  //number of state: Intron or Exon
	int M;  //number of observation: A, T, C, G

	double piMatrix[2]; //the sequence might start at Intron or Exon
	double AMatrix[2][2]; //The transition probability between Intron and Exon

	double BMatrix[2][4]; //What is the probability of state of Intron or Exon when we observe one of the A, T, C, G.

	double denom;
	double numer;
	int T; //How many times or observations are going to be tested

	int* nucleotideSequence = NULL;

	double* scale = NULL;
	double** alpha = NULL; //the probability up to time t at each state
	double** beta = NULL; //the probability after time t at each state
	double** gammas = NULL;
	double*** digammas = NULL;
	int iter = 0;
	double diff;
	double oldLogProb = -1000000;
	double logProbability;
	bool goodModel = false;

public:

	HmmModel(int numOfStates, int numOfObs)
	{
		N = numOfStates;
		
		M = numOfObs;
		int i, j;
		for (i = 0; i < N; i++) { piMatrix[i] = 0; }
		for (i = 0; i < N; i++)
		{
			for (j = 0; j < N; j++) { AMatrix[i][j] = 0; }
		}
		for (i = 0; i < N; i++)
		{
			for (j = 0; j < M; j++) { AMatrix[i][j] = 0; }
		}
	}

	void initializeModel(int* nucleotidePtr) //initialize the A,B matrix and ¦Ð
	{
		nucleotideSequence = nucleotidePtr;
		int num = 100;
		srand((unsigned)time(0));

		for (int i = 0; i < N; i++) //set data to ¦Ð
		{
			piMatrix[i] = (double) (rand() % num);
		}

		for (int i = 0; i < N; i++) // set data to A matrix
		{
			for (int j = 0; j < N; j++) { AMatrix[i][j] = (double)(rand() % num); }
		}

		for (int i = 0; i < N; i++) // set data to B matrix
		{
			for (int j = 0; j < M; j++) { BMatrix[i][j] = (double)(rand() % num); }
		}

		normalizePi();
		normalizeA();
		normalizeB();
	}

	void normalizePi()
	{
		double piSum = 0;
		int i;
		for (i = 0; i < N; i++) { piSum += piMatrix[i]; }
		for (i = 0; i < N; i++) { piMatrix[i] = piMatrix[i] / piSum; }
	}

	void normalizeA()
	{
		int i, j;
		double ASum;
		for (i = 0; i <= N; i++)
		{
			ASum = 0.00000;
			for (j = 0; j < N; j++) { ASum += AMatrix[i][j]; }
			for (j = 0; j < N; j++) { AMatrix[i][j] = AMatrix[i][j] / ASum; }
		}
	}

	void normalizeB()
	{
		int i, j;
		double BSum;
		for (i = 0; i < N; i++)
		{
			BSum = 0.00000;
			for (j = 0; j < M; j++) { BSum += BMatrix[i][j]; }
			for (j = 0; j <= M - 1; j++) { BMatrix[i][j] = BMatrix[i][j] / BSum; }
		}
	}

	void printPi()
	{
		cout << "¦Ð= (";
		for (int i = 0; i < N; i++) { cout << piMatrix[i] << "  "; }
		cout << ")" << '\n';
	}

	void printAMatrixWFile(int state)
	{
		fstream myFile;
		if (state == 0) { myFile.open("initial A Matrix.txt", ios::out); }
		else { myFile.open("final A Matrix.txt", ios::out); }
		cout << "A =" << '\n';
		myFile << "A =" << '\n';
		for (int i = 0; i < N; i++)
		{
			cout << "(";
			myFile << "(";
			for (int j = 0; j < N; j++)
			{
				cout << AMatrix[i][j] << "  ";
				myFile << AMatrix[i][j] << "  ";
			}
			cout << ")" << endl;
			myFile << ")" << endl;
		}
		myFile.close();
	}

	void printBMatrixWFile(int state)
	{
		fstream myFile;
		if (state == 0) { myFile.open("initial B Matrix.txt", ios::out); }
		else { myFile.open("final B Matrix.txt", ios::out); }

		cout << "B =" << '\n';

		for (int j = 0; j < M; j++)
		{
			cout << setw(8) << left << getNucleotideName(j) << " : (";
			myFile << setw(12) << left << getNucleotideName(j) << " : (";
			for (int i = 0; i <= N - 1; i++)
			{
				cout << setw(12) << right << BMatrix[i][j] << "  ";
				myFile << setw(12) << right << BMatrix[i][j] << "  ";
			}
			cout << ")" << endl;
			myFile << ")" << endl;
		}
		myFile.close();
	}

	void printRowSum()
	{
		double sum;
		for (int i = 0; i < N; i++)
		{
			sum = 0;
			cout << "sum of " << i + 1 << " row of A matrix:" << endl;
			for (int j = 0; j < N; j++) { sum += AMatrix[i][j]; }
			cout << sum << endl;
		}
		for (int i = 0; i < N; i++)
		{
			sum = 0;
			cout << "sum of " << i + 1 << " row of B matrix:" << endl;
			for (int j = 0; j <= M - 1; j++) { sum += BMatrix[i][j]; }
			cout << sum << endl;
		}
	}

	//initialize the scale, alpha, beta, gammas and digammas
	void setData(int time)
	{
		T = time;
		scale = new double[T];
		alpha = new double* [T];//allocate space of T pointers that point to double, return the addr of the pointer pointing to those double pointer to alpha
		int t;
		for (t = 0; t < T; t++) { alpha[t] = new double[N]; }

		beta = new double* [T];
		for (t = 0; t < T; t++) { beta[t] = new double[N]; }

		gammas = new double* [T];
		for (t = 0; t < T; t++) { gammas[t] = new double[N]; }

		digammas = new double** [T];//allocate space of T pointers that point to the pointer of double, return the address of the first pointer to digammas
		for (t = 0; t < T; t++)    //as the pointer digammas has, it (digammas) point to T pointers that every pointer point to some other pointers that point to double
		{
			digammas[t] = new double* [N];
			for (int i = 0; i <= N - 1; i++) { digammas[t][i] = new double[N]; }
		}
	}

	void freeAllPtr()
	{
		delete scale;
		scale = NULL;
		int t, i;
		for (t = 0; t < T; t++)
		{
			delete alpha[t];
			alpha[t] = NULL;
		}
		delete alpha;
		alpha = NULL;

		for (t = 0; t < T; t++)
		{
			delete gammas[t];
			gammas[t] = NULL;
		}
		delete gammas;
		gammas = NULL;

		for (t = 0; t < T; t++)
		{
			for (i = 0; i < N; i++)
			{
				delete digammas[t][i];
				digammas[t][i] = NULL;
			}
			delete digammas[t];
			digammas[t] = NULL;
		}
		delete digammas;
		digammas = NULL;
	}
	//forward algorithm
	void computeAlphaPass(int* ntPtr)
	{
		int i, j, t;
		scale[0] = 0;
		for (i = 0; i < N; i++)
		{
			alpha[0][i] = piMatrix[i] * (BMatrix[i][ntPtr[0]]);
			scale[0] += alpha[0][i];
		}
		scale[0] = ((double)1.0 / scale[0]);
		for (i = 0; i < N; i++)
		{
			alpha[0][i] = alpha[0][i] * scale[0];
		}
		for (t = 1; t < T; t++) //computer alpha[t][i]
		{
			scale[t] = 0;
			for (i = 0; i < N; i++)
			{
				alpha[t][i] = 0;
				for (j = 0; j < N; j++) { alpha[t][i] = alpha[t][i] + alpha[t - 1][j] * AMatrix[j][i]; }
				alpha[t][i] = alpha[t][i] * (BMatrix[i][ntPtr[t]]);
				scale[t] += alpha[t][i];
			}
			scale[t] = ((double)1.0 / scale[t]);

			for (i = 0; i < N; i++) { alpha[t][i] = scale[t] * alpha[t][i]; }
		}
	}
	//backward algorithm
	void computeBetaPass(int* ntPtr)
	{
		int i, j, t;
		for (i = 0; i < N; i++) { beta[T - 1][i] = scale[T - 1]; }
		for (t = T - 2; t >= 0; t--) //computer beta pass
		{
			for (i = 0; i < N; i++)
			{
				beta[t][i] = 0;
				for (j = 0; j < N; j++)
				{
					beta[t][i] = beta[t][i] + AMatrix[i][j] * (BMatrix[j][ntPtr[t + 1]]) * beta[t + 1][j];
				}
				beta[t][i] = scale[t] * beta[t][i];
			}
		}
	}
	//baum-welch algorithm
	void computeGammas(int* ntPtr)
	{
		int i, j, t;
		/*double gammasTemp;
		double alphaTemp;*/
		for (t = 0; t <= T - 2; t++)
		{
			denom = 0;
			for (i = 0; i <= N - 1; i++)
			{
				//alphaTemp = alpha[t][i];
				for (j = 0; j <= N - 1; j++)
				{
					denom = denom + alpha[t][i] * AMatrix[i][j] * (BMatrix[j][ntPtr[t + 1]]) * beta[t + 1][j];
				}
			}
			for (i = 0; i <= N - 1; i++)
			{
				//gammasTemp = 0;  //in the j loop, there is no changing of alpha[t][i], because it will not change the t and i in j loop
				//alphaTemp = alpha[t][i];
				gammas[t][i] = 0;
				for (j = 0; j < N; j++)
				{
					digammas[t][i][j] = (alpha[t][i] * AMatrix[i][j] * (BMatrix[j][ntPtr[t + 1]]) * beta[t + 1][j] / denom);
					gammas[t][i] = gammas[t][i] + digammas[t][i][j];
				}
			}
		}

		denom = 0; //Special case for gammas[T-1][i]
		for (i = 0; i < N; i++) { denom = denom + alpha[T - 1][i]; }
		for (i = 0; i < N; i++) { gammas[T - 1][i] = alpha[T - 1][i] / denom; }
	}
	//re-estimate the pi, A and B matrix of the model
	void reestimate(int* ntPtr)
	{
		int i, j, t;
		for (i = 0; i < N; i++) { piMatrix[i] = gammas[0][i]; } //re-estimate pi
		for (i = 0; i < N; i++)   //re-estimate A
		{
			for (j = 0; j < N; j++)
			{
				numer = 0;
				denom = 0;
				for (t = 0; t <= T - 2; t++)
				{
					numer = numer + digammas[t][i][j];
					denom = denom + gammas[t][i];
				}
				AMatrix[i][j] = numer / denom;
			}
		}
		for (i = 0; i < N; i++)  //re-estimate B
		{
			for (j = 0; j < M; j++)
			{
				numer = 0;
				denom = 0;
				for (t = 0; t <= T - 1; t++)
				{
					if (ntPtr[t] == j) { numer = numer + gammas[t][i]; }
					denom = denom + gammas[t][i];
				}
				BMatrix[i][j] = numer / denom;
			}
		}
	}
	//return the letter of nucleotide from int
	char returnToNT(int n)
	{
		if (n == 0) { return 'A';  }
		else if (n == 1) { return 'T'; }
		else if (n == 2) { return 'C'; }
		else if (n == 3) { return 'G'; }
		return '-';	
	}
	//get the number of observation
	int getT() { return T; }

	double* getScale() { return scale; }

	void setOldProlog(double num) { oldLogProb = num; }

	double getOldProlog() { return oldLogProb; }

	void setNewProlog(double num) { logProbability = num; }

	double getNewProlog() { return logProbability; }

	void setDiff(double num) { diff = num; }

	double getDiff() { return diff; }

	bool checkIfGoodModel() { return goodModel; }

	void setGoodModel(bool condition) { goodModel = condition; }

	double** getGammas() { return gammas; }

	double* getPi() { return piMatrix; }

	double* getA(int i) { return AMatrix[i]; }										
	double* getB(int i) { return BMatrix[i]; } 

	//copy the pi matrix from result of training model
	void copyPi(HmmModel* training)
	{
		for (int i = 0; i < N; i++) { piMatrix[i] = (training->getPi())[i]; }
	}

	//copy the A matrix from result of training model
	void copyA(HmmModel* training)
	{
		for (int i = 0; i < N; i++)
		{
			for (int j = 0; j < N; j++) { AMatrix[i][j] = (training->getA(i))[j]; }
		}
	}

	//copy the B matrix from result of training model
	void copyB(HmmModel* training)
	{
		for (int i = 0; i < N; i++)
		{
			for (int j = 0; j < M; j++) { BMatrix[i][j] = (training->getB(i))[j]; }
		}
	}

	//add 1 to the values of B matrix if some probabilities are very small
	void BMatrixAddNum(double num)
	{
		for (int i = 0; i < N; i++)
		{
			for (int j = 0; j < M; j++) { BMatrix[i][j] = BMatrix[i][j] + num; }
		}
		normalizeB();
	}

	int* getNucleotideSequence() { return nucleotideSequence; }

	//return the char of the nucleotide from int
	char getNucleotideName(int num)
	{
		switch (num)
		{
		case 0: return 'A';
			break;
		case 1: return 'T';
			break;
		case 2: return 'C';
			break;
		case 3: return 'G';
			break;
		default: return '-';
			break;
		}
	}


};

#endif

