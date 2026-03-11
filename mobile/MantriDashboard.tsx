import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
  PermissionsAndroid,
  Platform,
  Linking,
  RefreshControl,
  ActivityIndicator
} from 'react-native';
import {
  FAB,
  Card,
  Button,
  Avatar,
  Badge,
  ProgressBar,
  Chip,
  Divider,
  Surface,
  Title,
  Paragraph,
  IconButton
} from 'react-native-paper';
import {
  launchCamera,
  launchImageLibrary,
  ImagePickerResponse,
  MediaType,
} from 'react-native-image-picker';
import Geolocation from 'react-native-geolocation-service';
import BluetoothManager from 'react-native-bluetooth-manager';
import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';
import SQLite from 'react-native-sqlite-storage';

// Services
import { ApiService } from '../services/ApiService';
import { LocationService } from '../services/LocationService';
import { SyncService } from '../services/SyncService';
import { PrinterService } from '../services/PrinterService';

interface Member {
  id: number;
  name: string;
  phone: string;
  credit_score: number;
  location: {
    lat: number;
    lng: number;
    address: string;
  };
  distance?: number;
}

interface CashPosition {
  current_amount: number;
  limit: number;
  utilization: number;
  status: 'normal' | 'warning' | 'critical';
}

interface DailyTarget {
  target: number;
  collected: number;
  remaining: number;
  percentage: number;
}

const MantriDashboard: React.FC = () => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [profile, setProfile] = useState<any>(null);
  const [cashPosition, setCashPosition] = useState<CashPosition | null>(null);
  const [dailyTarget, setDailyTarget] = useState<DailyTarget | null>(null);
  const [nearbyMembers, setNearbyMembers] = useState<Member[]>([]);
  const [currentLocation, setCurrentLocation] = useState<{lat: number, lng: number} | null>(null);
  const [gpsEnabled, setGpsEnabled] = useState(false);
  const [isOnline, setIsOnline] = useState(true);
  const [pendingSync, setPendingSync] = useState(0);

  // Initialize database
  const [db, setDb] = useState<SQLite.SQLiteDatabase | null>(null);

  useEffect(() => {
    initializeApp();
  }, []);

  const initializeApp = async () => {
    try {
      // Initialize SQLite database
      const database = await SQLite.openDatabase({
        name: 'mantri_offline.db',
        location: 'default',
      });
      setDb(database);
      
      // Create tables if not exist
      await createTables(database);
      
      // Check network status
      NetInfo.addEventListener(state => {
        setIsOnline(state.isConnected);
      });
      
      // Request permissions
      await requestPermissions();
      
      // Load data
      await loadDashboardData();
      
      // Start location tracking
      await startLocationTracking();
      
      setLoading(false);
    } catch (error) {
      console.error('App initialization error:', error);
      Alert.alert('Error', 'Failed to initialize app');
    }
  };

  const createTables = async (database: SQLite.SQLiteDatabase) => {
    const queries = [
      `CREATE TABLE IF NOT EXISTS offline_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid TEXT UNIQUE,
        transaction_type TEXT,
        amount REAL,
        member_id INTEGER,
        reference_number TEXT,
        description TEXT,
        location_lat REAL,
        location_lng REAL,
        created_at TEXT,
        synced INTEGER DEFAULT 0,
        sync_attempts INTEGER DEFAULT 0
      )`,
      `CREATE TABLE IF NOT EXISTS offline_members (
        id INTEGER PRIMARY KEY,
        uuid TEXT UNIQUE,
        name TEXT,
        phone TEXT,
        credit_score INTEGER,
        location_lat REAL,
        location_lng REAL,
        location_address TEXT,
        last_updated TEXT
      )`,
      `CREATE TABLE IF NOT EXISTS sync_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        table_name TEXT,
        record_id INTEGER,
        action TEXT,
        data TEXT,
        created_at TEXT,
        attempts INTEGER DEFAULT 0
      )`
    ];

    for (const query of queries) {
      await database.executeSql(query);
    }
  };

  const requestPermissions = async () => {
    if (Platform.OS === 'android') {
      const granted = await PermissionsAndroid.requestMultiple([
        PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
        PermissionsAndroid.PERMISSIONS.ACCESS_COARSE_LOCATION,
        PermissionsAndroid.PERMISSIONS.CAMERA,
        PermissionsAndroid.PERMISSIONS.WRITE_EXTERNAL_STORAGE,
        PermissionsAndroid.PERMISSIONS.BLUETOOTH,
        PermissionsAndroid.PERMISSIONS.BLUETOOTH_ADMIN,
      ]);

      const locationGranted = granted[PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION] === PermissionsAndroid.RESULTS.GRANTED;
      setGpsEnabled(locationGranted);

      if (!locationGranted) {
        Alert.alert(
          'Location Permission Required',
          'This app requires location permission to verify customer locations',
          [
            { text: 'Cancel', style: 'cancel' },
            { text: 'Settings', onPress: () => Linking.openSettings() }
          ]
        );
      }
    }
  };

  const startLocationTracking = async () => {
    try {
      const hasPermission = await Geolocation.requestAuthorization();
      
      if (hasPermission) {
        Geolocation.getCurrentPosition(
          (position) => {
            const { latitude, longitude } = position.coords;
            setCurrentLocation({ lat: latitude, lng: longitude });
            setGpsEnabled(true);
            loadNearbyMembers(latitude, longitude);
          },
          (error) => {
            console.error('Location error:', error);
            setGpsEnabled(false);
          },
          { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
        );

        // Watch position for real-time updates
        Geolocation.watchPosition(
          (position) => {
            const { latitude, longitude } = position.coords;
            setCurrentLocation({ lat: latitude, lng: longitude });
            loadNearbyMembers(latitude, longitude);
          },
          (error) => console.error('Watch position error:', error),
          { enableHighAccuracy: true, distanceFilter: 10 }
        );
      }
    } catch (error) {
      console.error('Location tracking error:', error);
    }
  };

  const loadDashboardData = async () => {
    try {
      const apiService = new ApiService();
      
      // Load profile
      const profileData = await apiService.getProfile();
      setProfile(profileData.data);
      
      // Load cash position
      const cashData = await apiService.getCashPosition();
      setCashPosition(cashData.data);
      
      // Load daily target
      const targetData = await apiService.getDailyTarget();
      setDailyTarget(targetData.data);
      
      // Load pending sync count
      const syncService = new SyncService(db!);
      const pendingCount = await syncService.getPendingSyncCount();
      setPendingSync(pendingCount);
      
    } catch (error) {
      console.error('Dashboard data load error:', error);
      // Load from offline cache if network fails
      await loadOfflineData();
    }
  };

  const loadOfflineData = async () => {
    try {
      // Load cached profile
      const cachedProfile = await AsyncStorage.getItem('cached_profile');
      if (cachedProfile) {
        setProfile(JSON.parse(cachedProfile));
      }
      
      // Load cached cash position
      const cachedCash = await AsyncStorage.getItem('cached_cash_position');
      if (cachedCash) {
        setCashPosition(JSON.parse(cachedCash));
      }
      
      // Load cached daily target
      const cachedTarget = await AsyncStorage.getItem('cached_daily_target');
      if (cachedTarget) {
        setDailyTarget(JSON.parse(cachedTarget));
      }
      
    } catch (error) {
      console.error('Offline data load error:', error);
    }
  };

  const loadNearbyMembers = async (lat: number, lng: number) => {
    try {
      const locationService = new LocationService();
      const members = await locationService.getNearbyMembers(lat, lng, 5000); // 5km radius
      
      // Sort by distance
      members.sort((a, b) => (a.distance || 0) - (b.distance || 0));
      
      // Take top 10
      setNearbyMembers(members.slice(0, 10));
      
      // Cache members offline
      if (db) {
        for (const member of members) {
          await db.executeSql(
            `INSERT OR REPLACE INTO offline_members 
             (id, uuid, name, phone, credit_score, location_lat, location_lng, location_address, last_updated)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [member.id, member.uuid, member.name, member.phone, member.credit_score,
             member.location.lat, member.location.lng, member.location.address, new Date().toISOString()]
          );
        }
      }
      
    } catch (error) {
      console.error('Load nearby members error:', error);
      // Load from offline cache
      await loadOfflineMembers();
    }
  };

  const loadOfflineMembers = async () => {
    if (!db) return;
    
    try {
      const results = await db.executeSql(`
        SELECT * FROM offline_members 
        ORDER BY last_updated DESC 
        LIMIT 10
      `);
      
      const members: Member[] = [];
      for (let i = 0; i < results[0].rows.length; i++) {
        const row = results[0].rows.item(i);
        members.push({
          id: row.id,
          name: row.name,
          phone: row.phone,
          credit_score: row.credit_score,
          location: {
            lat: row.location_lat,
            lng: row.location_lng,
            address: row.location_address
          }
        });
      }
      
      setNearbyMembers(members);
    } catch (error) {
      console.error('Load offline members error:', error);
    }
  };

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await loadDashboardData();
      if (currentLocation) {
        await loadNearbyMembers(currentLocation.lat, currentLocation.lng);
      }
    } catch (error) {
      console.error('Refresh error:', error);
    } finally {
      setRefreshing(false);
    }
  }, [currentLocation]);

  const handleQRScan = () => {
    // Navigate to QR Scanner
    // This would integrate with react-native-camera
    Alert.alert('QR Scanner', 'QR Scanner functionality would be implemented here');
  };

  const handleMemberSelect = (member: Member) => {
    // Navigate to member details/payment screen
    Alert.alert('Member Selected', `Selected: ${member.name}`);
  };

  const handleSync = async () => {
    if (!isOnline) {
      Alert.alert('Offline', 'No internet connection available');
      return;
    }
    
    try {
      const syncService = new SyncService(db!);
      const result = await syncService.syncAll();
      
      if (result.success) {
        setPendingSync(0);
        Alert.alert('Sync Complete', `Synced ${result.synced} items`);
        await loadDashboardData();
      } else {
        Alert.alert('Sync Failed', result.error);
      }
    } catch (error) {
      console.error('Sync error:', error);
      Alert.alert('Sync Error', 'Failed to sync data');
    }
  };

  const getCashStatusColor = (status: string) => {
    switch (status) {
      case 'normal': return '#4CAF50';
      case 'warning': return '#FF9800';
      case 'critical': return '#F44336';
      default: return '#9E9E9E';
    }
  };

  const getCreditScoreColor = (score: number) => {
    if (score >= 700) return '#4CAF50';
    if (score >= 500) return '#FF9800';
    return '#F44336';
  };

  const renderCashPosition = () => {
    if (!cashPosition) return null;
    
    return (
      <Card style={styles.card}>
        <Card.Content>
          <View style={styles.cardHeader}>
            <Title>Dompet Tunai Saya</Title>
            <Badge
              style={[styles.statusBadge, { backgroundColor: getCashStatusColor(cashPosition.status) }]}
            >
              {cashPosition.status.toUpperCase()}
            </Badge>
          </View>
          
          <View style={styles.cashContainer}>
            <Text style={styles.cashAmount}>
              Rp {cashPosition.current_amount.toLocaleString('id-ID')}
            </Text>
            <Text style={styles.cashLimit}>Limit: Rp {cashPosition.limit.toLocaleString('id-ID')}</Text>
          </View>
          
          <ProgressBar
            progress={cashPosition.utilization / 100}
            color={getCashStatusColor(cashPosition.status)}
            style={styles.progressBar}
          />
          
          <Text style={styles.utilizationText}>
            {cashPosition.utilization.toFixed(1)}% penuh
          </Text>
        </Card.Content>
      </Card>
    );
  };

  const renderDailyTarget = () => {
    if (!dailyTarget) return null;
    
    return (
      <Card style={styles.card}>
        <Card.Content>
          <Title>Ringkasan Hari Ini</Title>
          
          <View style={styles.targetRow}>
            <View style={styles.targetItem}>
              <Text style={styles.targetLabel}>Target</Text>
              <Text style={styles.targetValue}>{dailyTarget.target} Orang</Text>
            </View>
            <View style={styles.targetItem}>
              <Text style={styles.targetLabel}>Sisa</Text>
              <Text style={styles.targetValue}>{dailyTarget.remaining} Orang</Text>
            </View>
          </View>
          
          <View style={styles.targetRow}>
            <View style={styles.targetItem}>
              <Text style={styles.targetLabel}>Setoran</Text>
              <Text style={styles.targetValue}>Rp {dailyTarget.collected.toLocaleString('id-ID')}</Text>
            </View>
            <View style={styles.targetItem}>
              <Text style={styles.targetLabel}>Progress</Text>
              <Text style={styles.targetValue}>{dailyTarget.percentage.toFixed(1)}%</Text>
            </View>
          </View>
          
          <ProgressBar
            progress={dailyTarget.percentage / 100}
            color="#4CAF50"
            style={styles.progressBar}
          />
        </Card.Content>
      </Card>
    );
  };

  const renderNearbyMembers = () => {
    return (
      <Card style={styles.card}>
        <Card.Content>
          <View style={styles.cardHeader}>
            <Title>Nasabah Terdekat</Title>
            <View style={styles.gpsStatus}>
              <IconButton
                icon={gpsEnabled ? "map-marker" : "map-marker-off"}
                size={20}
                color={gpsEnabled ? "#4CAF50" : "#F44336"}
              />
              <Text style={styles.gpsText}>GPS: {gpsEnabled ? 'ON' : 'OFF'}</Text>
            </View>
          </View>
          
          {nearbyMembers.map((member, index) => (
            <View key={member.id}>
              <TouchableOpacity
                style={styles.memberItem}
                onPress={() => handleMemberSelect(member)}
              >
                <View style={styles.memberInfo}>
                  <Avatar.Text size={40} label={member.name.charAt(0)} />
                  <View style={styles.memberDetails}>
                    <Text style={styles.memberName}>{member.name}</Text>
                    <Text style={styles.memberPhone}>{member.phone}</Text>
                    <View style={styles.memberMeta}>
                      <Chip
                        style={[styles.creditChip, { backgroundColor: getCreditScoreColor(member.credit_score) }]}
                        textStyle={{ color: 'white' }}
                      >
                        ⭐ {member.credit_score}
                      </Chip>
                      {member.distance && (
                        <Text style={styles.memberDistance}>
                          {member.distance < 1000 ? `${member.distance}m` : `${(member.distance / 1000).toFixed(1)}km`}
                        </Text>
                      )}
                    </View>
                  </View>
                </View>
                <IconButton icon="chevron-right" size={20} />
              </TouchableOpacity>
              {index < nearbyMembers.length - 1 && <Divider />}
            </View>
          ))}
        </Card.Content>
      </Card>
    );
  };

  const renderSyncStatus = () => {
    return (
      <View style={styles.syncContainer}>
        <Surface style={styles.syncCard}>
          <View style={styles.syncContent}>
            <View>
              <Text style={styles.syncTitle}>Sync Status</Text>
              <Text style={styles.syncText}>
                {pendingSync > 0 ? `${pendingSync} pending items` : 'All synced'}
              </Text>
            </View>
            <Button
              mode="contained"
              onPress={handleSync}
              disabled={!isOnline || pendingSync === 0}
              style={styles.syncButton}
            >
              Sync
            </Button>
          </View>
        </Surface>
      </View>
    );
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#667eea" />
        <Text style={styles.loadingText}>Loading dashboard...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* Header */}
        <Surface style={styles.header}>
          <View style={styles.headerContent}>
            <View>
              <Text style={styles.headerTitle}>Halo, {profile?.name}</Text>
              <Text style={styles.headerSubtitle}>{profile?.code} • {profile?.area}</Text>
            </View>
            <Avatar.Text size={50} label={profile?.name?.charAt(0) || 'M'} />
          </View>
        </Surface>

        {/* Cash Position */}
        {renderCashPosition()}

        {/* Daily Target */}
        {renderDailyTarget()}

        {/* Nearby Members */}
        {renderNearbyMembers()}

        {/* Sync Status */}
        {renderSyncStatus()}
      </ScrollView>

      {/* Floating Action Button */}
      <FAB
        style={styles.fab}
        icon="qrcode"
        onPress={handleQRScan}
        label="SCAN QR NASABAH"
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  scrollView: {
    flex: 1,
  },
  header: {
    padding: 20,
    marginBottom: 10,
    elevation: 4,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#2c3e50',
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#6c757d',
    marginTop: 4,
  },
  card: {
    margin: 10,
    marginBottom: 5,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 15,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  cashContainer: {
    marginBottom: 15,
  },
  cashAmount: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#2c3e50',
  },
  cashLimit: {
    fontSize: 14,
    color: '#6c757d',
    marginTop: 4,
  },
  progressBar: {
    height: 8,
    borderRadius: 4,
    marginVertical: 10,
  },
  utilizationText: {
    fontSize: 12,
    color: '#6c757d',
    textAlign: 'center',
  },
  targetRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 15,
  },
  targetItem: {
    flex: 1,
    alignItems: 'center',
  },
  targetLabel: {
    fontSize: 12,
    color: '#6c757d',
    marginBottom: 4,
  },
  targetValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#2c3e50',
  },
  gpsStatus: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  gpsText: {
    fontSize: 12,
    color: '#6c757d',
    marginLeft: 4,
  },
  memberItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
  },
  memberInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  memberDetails: {
    marginLeft: 15,
    flex: 1,
  },
  memberName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#2c3e50',
  },
  memberPhone: {
    fontSize: 14,
    color: '#6c757d',
    marginTop: 2,
  },
  memberMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 8,
  },
  creditChip: {
    marginRight: 10,
  },
  memberDistance: {
    fontSize: 12,
    color: '#6c757d',
  },
  syncContainer: {
    padding: 10,
  },
  syncCard: {
    padding: 15,
    elevation: 2,
  },
  syncContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  syncTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#2c3e50',
  },
  syncText: {
    fontSize: 14,
    color: '#6c757d',
    marginTop: 2,
  },
  syncButton: {
    marginLeft: 15,
  },
  fab: {
    position: 'absolute',
    margin: 16,
    right: 0,
    bottom: 0,
    backgroundColor: '#667eea',
  },
});

export default MantriDashboard;
