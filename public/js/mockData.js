const defaultData = {
  rawMaterialsList: [
    { id: 'rm1', name: 'Fresh Garlic', image: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm2', name: 'Fresh Onion', image: 'https://images.unsplash.com/photo-1618512496248-a07fe83aa8cb?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm3', name: 'Fresh Tomato', image: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm4', name: 'Fresh Ginger', image: 'https://images.unsplash.com/photo-1596368708356-6e1ea8f8cece?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm5', name: 'Fresh Beetroot', image: 'https://images.unsplash.com/photo-1593105544559-ecb03bf76f82?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm6', name: 'Fresh Carrot', image: 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm7', name: 'Fresh Potato', image: 'https://images.unsplash.com/photo-1518977676601-b53f82ber540?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm8', name: 'Fresh Spinach', image: 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm9', name: 'Fresh Mint Leaves', image: 'https://images.unsplash.com/photo-1628556270448-4d4e4148e1b1?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm10', name: 'Fresh Coriander Leaves', image: 'https://images.unsplash.com/photo-1592928302636-c83cf1e1c887?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm11', name: 'Fresh Curry Leaves', image: 'https://images.unsplash.com/photo-1601493700631-2b16ec4b4716?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm12', name: 'Fresh Green Chili', image: 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm13', name: 'Fresh Cabbage', image: 'https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm14', name: 'Fresh Amla', image: 'https://images.unsplash.com/photo-1585059895524-72f80dc7e03c?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm15', name: 'Fresh Mango', image: 'https://images.unsplash.com/photo-1553279768-865429fa0078?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm16', name: 'Fresh Banana', image: 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm17', name: 'Fresh Papaya', image: 'https://images.unsplash.com/photo-1517282009859-f000ec3b26fe?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm18', name: 'Fresh Guava', image: 'https://images.unsplash.com/photo-1536511132770-e5058c7e8c46?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm19', name: 'Fresh Apple', image: 'https://images.unsplash.com/photo-1568702846914-96b305d2ead1?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm20', name: 'Fresh Pineapple', image: 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm21', name: 'Fresh Orange', image: 'https://images.unsplash.com/photo-1547514701-42fee3e1c750?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm22', name: 'Fresh Pomegranate', image: 'https://images.unsplash.com/photo-1541159067299-80c0e8bac5e0?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm23', name: 'Fresh Jamun', image: 'https://images.unsplash.com/photo-1597714026720-8f74c62310ba?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm24', name: 'Fresh Chickoo', image: 'https://images.unsplash.com/photo-1605027990121-cbae9e0642df?w=200&h=200&fit=crop', unit: 'kg' },
    { id: 'rm25', name: 'Fresh Custard Apple', image: 'https://images.unsplash.com/photo-1634315510935-e8f52c677891?w=200&h=200&fit=crop', unit: 'kg' }
  ],
  products: [
    // --- SEMI PRODUCTS (from user list) ---
    { id: 's1', name: 'Tomato powder', type: 'SEMI', unit: 'kg' },
    { id: 's2', name: 'Lemon Powder', type: 'SEMI', unit: 'kg' },
    { id: 's3', name: 'Tamarind Powder', type: 'SEMI', unit: 'kg' },
    { id: 's4', name: 'Beetroot Powder', type: 'SEMI', unit: 'kg' },
    { id: 's5', name: 'Green Chili Powder', type: 'SEMI', unit: 'kg' },
    { id: 's6', name: 'Capsicum Powder', type: 'SEMI', unit: 'kg' },
    { id: 's7', name: 'Cheese Powder / Cheddar Cheese', type: 'SEMI', unit: 'kg' },
    { id: 's8', name: 'Mango Powder', type: 'SEMI', unit: 'kg' },
    { id: 's9', name: 'Fig Powder', type: 'SEMI', unit: 'kg' },
    { id: 's10', name: 'Pineapple Powder', type: 'SEMI', unit: 'kg' },
    { id: 's11', name: 'Papaya Powder', type: 'SEMI', unit: 'kg' },
    { id: 's12', name: 'Soya Sauce Powder', type: 'SEMI', unit: 'kg' },
    { id: 's13', name: 'HVP powder Groundnut Base', type: 'SEMI', unit: 'kg' },
    { id: 's14', name: 'HVP powder Soya Base', type: 'SEMI', unit: 'kg' },
    { id: 's15', name: 'Onion Flakes', type: 'SEMI', unit: 'kg' },
    { id: 's16', name: 'Onion Powder', type: 'SEMI', unit: 'kg' },
    { id: 's17', name: 'Garlic Flakes', type: 'SEMI', unit: 'kg' },
    { id: 's18', name: 'Garlic Powder', type: 'SEMI', unit: 'kg' },
    { id: 's19', name: 'Dried Mango (Amchur) powder', type: 'SEMI', unit: 'kg' },
    { id: 's20', name: 'Potato Flakes', type: 'SEMI', unit: 'kg' },
    { id: 's21', name: 'Potato Powder', type: 'SEMI', unit: 'kg' },
    { id: 's22', name: 'Ginger powder', type: 'SEMI', unit: 'kg' },
    { id: 's23', name: 'Spinach Powder', type: 'SEMI', unit: 'kg' },
    { id: 's24', name: 'Masala tea', type: 'SEMI', unit: 'kg' },
    { id: 's25', name: 'Garam Masala', type: 'SEMI', unit: 'kg' },
    { id: 's26', name: 'Cumin Powder', type: 'SEMI', unit: 'kg' },
    { id: 's27', name: 'Turmeric Powder', type: 'SEMI', unit: 'kg' },
    { id: 's28', name: 'Red Chili Powder', type: 'SEMI', unit: 'kg' },
    { id: 's29', name: 'Coriander Powder', type: 'SEMI', unit: 'kg' },
    { id: 's30', name: 'Black Pepper Powder', type: 'SEMI', unit: 'kg' },
    { id: 's31', name: 'Coriander Cumin Powder', type: 'SEMI', unit: 'kg' },
    { id: 's32', name: 'Red Chili Flakes (Pizza Cut)', type: 'SEMI', unit: 'kg' },
    { id: 's33', name: 'Magic Masala', type: 'SEMI', unit: 'kg' },
    { id: 's34', name: 'Chatpata Masala', type: 'SEMI', unit: 'kg' },
    { id: 's35', name: 'Peri Peri Masala', type: 'SEMI', unit: 'kg' },
    { id: 's36', name: 'Schezwan Masala', type: 'SEMI', unit: 'kg' },
    { id: 's37', name: 'Masala Masti', type: 'SEMI', unit: 'kg' },
    { id: 's38', name: 'Garlic In Brine', type: 'SEMI', unit: 'kg' },
    
    // --- FINISHED PRODUCTS (for later) ---
    { id: 'f1', name: 'Packaged Tomato Powder', type: 'FINISHED', unit: 'boxes' }
  ],
  grades: [
    'PPF', 'TPR', 'TPS', 'GOLD', 'PREMIUM', 'RICH', 'RICH+', 'EXTRA STRONG', 'REGULAR', 'DELUXE', 'PURE'
  ],
  rawStock: [], 
  semiStock: [],
  finishedStock: [],
  orders: [], 
  users: [
    { id: 'u1', name: 'Admin', role: 'ADMIN', parentId: null, status: 'ACTIVE' },
    { id: 'u2', name: 'Raw Manager', role: 'RAW', parentId: 'u1', status: 'ACTIVE' },
    { id: 'u3', name: 'Semi Producer', role: 'SEMI', parentId: 'u1', status: 'ACTIVE' },
    { id: 'u4', name: 'Finished Producer', role: 'FINISHED', parentId: 'u1', status: 'ACTIVE' },
    { id: 'u5', name: 'Sales Exec', role: 'SALES', parentId: 'u1', status: 'ACTIVE' },
    { id: 'u6', name: 'Dispatch', role: 'DISPATCH', parentId: 'u1', status: 'ACTIVE' },
    { id: 'u7', name: 'Cashier', role: 'CASHIER', parentId: 'u1', status: 'ACTIVE' }
  ],
  transactions: [],
  companies: [],
  transportCompanies: [],
  productionLogs: [],
  dispatchLogs: [],
  purchaseOrders: []
};

const DB = {
  init() {
    if (!localStorage.getItem('simDB_v6')) {
      localStorage.setItem('simDB_v6', JSON.stringify(defaultData));
    }
  },
  get(key) {
    const data = JSON.parse(localStorage.getItem('simDB_v6'));
    return key ? data[key] : data;
  },
  set(key, value) {
    const data = JSON.parse(localStorage.getItem('simDB_v6'));
    data[key] = value;
    localStorage.setItem('simDB_v6', JSON.stringify(data));
  },
  reset() {
    localStorage.setItem('simDB_v6', JSON.stringify(defaultData));
  },
  generateId() {
    return Math.random().toString(36).substr(2, 9);
  }
};

DB.init();
