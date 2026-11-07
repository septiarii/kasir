#!/usr/bin/env python3
# python/sarima_forecast.py - Perbaikan Uji Stasioneritas
import sys
import json
import warnings
warnings.filterwarnings('ignore')

try:
    import numpy as np
    import pandas as pd
    from statsmodels.tsa.statespace.sarimax import SARIMAX
    from statsmodels.tsa.stattools import adfuller, kpss
    from sklearn.metrics import mean_absolute_error, mean_squared_error
    LIBRARIES_OK = True
except ImportError as e:
    LIBRARIES_OK = False
    ERROR_MESSAGE = str(e)

def safe_print(msg):
    try:
        print(msg)
    except UnicodeEncodeError:
        import re
        print(re.sub(r'[^\x00-\x7F]+', '', msg))

def test_stationarity(data_array):
    """
    Uji stasioneritas yang lebih komprehensif
    Returns: (is_stationary, adf_stat, adf_pvalue, interpretation, details)
    """
    n = len(data_array)
    details = {}
    
    # Untuk data sangat kecil, tidak bisa uji ADF
    if n < 4:
        return True, 0, 0.5, "Data terlalu sedikit untuk uji ADF (dianggap stasioner)", details
    
    try:
        # ===== ADF TEST =====
        adf_result = adfuller(data_array, autolag='AIC')
        adf_stat = float(adf_result[0])
        adf_pvalue = float(adf_result[1])
        adf_critical = adf_result[4]
        
        details['adf_statistic'] = adf_stat
        details['adf_pvalue'] = adf_pvalue
        details['adf_critical_1'] = float(adf_critical.get('1%', 0))
        details['adf_critical_5'] = float(adf_critical.get('5%', 0))
        details['adf_critical_10'] = float(adf_critical.get('10%', 0))
        
        # ===== KPSS TEST =====
        try:
            kpss_result = kpss(data_array, regression='c', nlags='auto')
            kpss_stat = float(kpss_result[0])
            kpss_pvalue = float(kpss_result[1])
            details['kpss_statistic'] = kpss_stat
            details['kpss_pvalue'] = kpss_pvalue
        except:
            details['kpss_statistic'] = None
            details['kpss_pvalue'] = None
        
        # ===== INTERPRETASI =====
        # ADF: H0 = tidak stasioner, Ha = stasioner
        # Jika p-value < 0.05, tolak H0, data stasioner
        
        if adf_pvalue < 0.01:
            is_stationary = True
            interpretation = "Data STASIONER (sangat signifikan, p < 0.01)"
            confidence = "Sangat Tinggi"
        elif adf_pvalue < 0.05:
            is_stationary = True
            interpretation = "Data STASIONER (signifikan, p < 0.05)"
            confidence = "Tinggi"
        elif adf_pvalue < 0.10:
            is_stationary = False
            interpretation = "Data MENDekati stasioner (p < 0.10)"
            confidence = "Rendah"
        else:
            is_stationary = False
            interpretation = "Data TIDAK STASIONER (p >= 0.10)"
            confidence = "Tidak Stasioner"
        
        details['confidence'] = confidence
        details['interpretation'] = interpretation
        
        return is_stationary, adf_stat, adf_pvalue, interpretation, details
        
    except Exception as e:
        safe_print(f"ADF test error: {str(e)}")
        return True, 0, 0.5, f"Uji ADF gagal: {str(e)}", details


def determine_d(data_array, is_stationary):
    """
    Tentukan orde differencing (d)
    """
    if is_stationary:
        return 0
    
    d = 0
    current_data = data_array.copy()
    
    # Coba differencing maksimal 2 kali
    for i in range(1, 3):
        if len(current_data) < 4:
            break
        try:
            current_data = np.diff(current_data)
            adf_result = adfuller(current_data, autolag='AIC')
            if adf_result[1] < 0.05:
                d = i
                break
            else:
                d = i  # Tetap simpan d terakhir
        except:
            break
    
    return d


def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No data provided'}))
        return
    
    if not LIBRARIES_OK:
        print(json.dumps({
            'error': f'Library tidak ditemukan: {ERROR_MESSAGE}',
            'solution': 'pip install numpy pandas statsmodels scikit-learn'
        }))
        return
    
    try:
        data = json.loads(sys.argv[1])
        
        if len(data) == 0:
            print(json.dumps({'error': 'No data available'}))
            return
        
        data_array = np.array(data, dtype=float)
        data_count = len(data)
        
        safe_print(f"Data received: {data_count} points")
        
        # =============================================================
        # STATISTIK DASAR
        # =============================================================
        stats = {
            'count': data_count,
            'mean': float(np.mean(data_array)),
            'std': float(np.std(data_array)),
            'min': float(np.min(data_array)),
            'max': float(np.max(data_array)),
            'sum': float(np.sum(data_array))
        }
        
        safe_print(f"Mean: Rp {stats['mean']:,.0f}")
        safe_print(f"Min: Rp {stats['min']:,.0f}")
        safe_print(f"Max: Rp {stats['max']:,.0f}")
        
        # =============================================================
        # UJI STASIONERITAS
        # =============================================================
        safe_print("\n--- STATIONARITY TEST ---")
        is_stationary, adf_stat, adf_pvalue, interpretation, stationarity_details = test_stationarity(data_array)
        
        safe_print(f"ADF Statistic: {adf_stat:.4f}")
        safe_print(f"ADF p-value: {adf_pvalue:.4f}")
        safe_print(f"Interpretation: {interpretation}")
        
        # Tentukan d
        d = determine_d(data_array, is_stationary)
        safe_print(f"Determined d = {d}")
        
        # =============================================================
        # TENTUKAN PARAMETER SARIMA
        # =============================================================
        if data_count >= 20:
            p, q = 1, 1
            P, D, Q, s = 1, 0, 1, 7
            safe_print("Mode: FULL SARIMA")
        elif data_count >= 10:
            p, q = 1, 1
            P, D, Q, s = 0, 0, 0, 0
            safe_print("Mode: SARIMA (no seasonal)")
        elif data_count >= 5:
            p, q = 1, 1
            P, D, Q, s = 0, 0, 0, 0
            safe_print("Mode: SARIMA simplified")
        elif data_count >= 2:
            p, q = 1, 0
            P, D, Q, s = 0, 0, 0, 0
            safe_print("Mode: SARIMA minimal")
        else:
            p, q = 0, 0
            P, D, Q, s = 0, 0, 0, 0
        
        # Pastikan d tidak melebihi data
        d_use = min(d, 2) if data_count >= 5 else 0
        
        # =============================================================
        # BUILD & FIT MODEL
        # =============================================================
        model_fit = None
        forecast_array = None
        mae = 0
        rmse = 0
        mape = None
        aic = 0
        bic = 0
        
        method_detail = f"SARIMA({p},{d_use},{q})" + (f"({P},{D},{Q}){s}" if s > 0 else "")
        safe_print(f"\nBuilding {method_detail}")
        
        try:
            if s > 0:
                model = SARIMAX(
                    data_array,
                    order=(p, d_use, q),
                    seasonal_order=(P, D, Q, s),
                    enforce_stationarity=False,
                    enforce_invertibility=False
                )
            else:
                model = SARIMAX(
                    data_array,
                    order=(p, d_use, q),
                    enforce_stationarity=False,
                    enforce_invertibility=False
                )
            
            model_fit = model.fit(disp=False, maxiter=100)
            forecast = model_fit.forecast(steps=30)
            forecast_array = np.abs(np.array(forecast))
            
            if np.all(forecast_array <= 0):
                raise Exception("Forecast menghasilkan 0")
            
            # Evaluasi
            try:
                fitted = model_fit.fittedvalues
                start_idx = max(0, len(data_array) - len(fitted))
                actual_aligned = data_array[start_idx:]
                
                if len(actual_aligned) > 0 and len(fitted) > 0:
                    mae = float(mean_absolute_error(actual_aligned, fitted))
                    rmse = float(np.sqrt(mean_squared_error(actual_aligned, fitted)))
                    
                    if np.all(actual_aligned != 0):
                        mape = float(np.mean(np.abs((actual_aligned - fitted) / actual_aligned)) * 100)
            except:
                pass
            
            aic = float(model_fit.aic)
            bic = float(model_fit.bic)
            
            safe_print(f"Model fitted! AIC: {aic:.2f}")
            
        except Exception as e:
            safe_print(f"SARIMA error: {str(e)}")
            forecast_array = fallback_forecast(data_array, data_count)
            method_detail += " (fallback)"
        
        # Validasi hasil
        if forecast_array is None or len(forecast_array) == 0:
            forecast_array = fallback_forecast(data_array, data_count)
        
        forecast_array = np.maximum(forecast_array, stats['min'] * 0.5 if stats['min'] > 0 else 1000)
        
        next_day = float(forecast_array[0])
        next_week = float(np.sum(forecast_array[:7]))
        next_month = float(np.sum(forecast_array))
        
        if next_day <= 0:
            next_day = stats['mean']
            next_week = stats['mean'] * 7
            next_month = stats['mean'] * 30
        
        # =============================================================
        # RESULT
        # =============================================================
        result = {
            'method': 'SARIMA',
            'method_detail': method_detail,
            'next_day': next_day,
            'next_week': next_week,
            'next_month': next_month,
            'forecast_30_days': [float(x) for x in forecast_array],
            'data_stats': stats,
            'stationarity': {
                'adf_statistic': adf_stat,
                'p_value': adf_pvalue,
                'is_stationary': is_stationary,
                'd': d_use,
                'interpretation': interpretation,
                'details': stationarity_details
            },
            'model': {
                'order': [p, d_use, q],
                'seasonal_order': [P, D, Q, s] if s > 0 else None,
                'aic': aic,
                'bic': bic,
                'mae': mae,
                'rmse': rmse,
                'mape': mape
            },
            'note': f'{method_detail} dengan {data_count} data'
        }
        
        safe_print(f"\nRESULT:")
        safe_print(f"   Next Day: Rp {next_day:,.0f}")
        safe_print(f"   Next Week: Rp {next_week:,.0f}")
        safe_print(f"   Next Month: Rp {next_month:,.0f}")
        
        print(json.dumps(result))
        
    except Exception as e:
        import traceback
        safe_print(f"ERROR: {str(e)}")
        traceback.print_exc()
        print(json.dumps({'error': str(e)}))


def fallback_forecast(data_array, data_count):
    """Fallback forecast"""
    mean_val = float(np.mean(data_array))
    last_val = float(data_array[-1])
    min_val = float(np.min(data_array))
    
    prediction = (0.6 * last_val) + (0.4 * mean_val)
    
    if prediction <= 0:
        prediction = mean_val if mean_val > 0 else (min_val if min_val > 0 else 1000)
    
    trend = 0
    if data_count > 1:
        trend = (float(data_array[-1]) - float(data_array[0])) / (data_count - 1)
    
    forecast = []
    for i in range(30):
        variation = 1 + (np.sin(i * np.pi / 3.5) * 0.03)
        forecast.append(max(0, prediction * variation + trend * i * 0.1))
    
    return np.array(forecast)


if __name__ == '__main__':
    main()