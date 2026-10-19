/* ════════════════════════════════════════════════════════════════
   TalipapaPOS — shared sample data for a general store (replace with your API/database later)
   Expiry dates are relative to today so every status shows in the demo.
   ════════════════════════════════════════════════════════════════ */
/* ────────────────────────────────────────────────
   General-store product catalog (used by the all-purpose POS).
   Fields: name, variant (size/flavor), brand (maker, optional), category,
           sku (barcode), unit, stock, reorder, price (VAT-incl.), cost,
           expiry (YYYY-MM-DD, or null for non-perishable items).
   IDs start at 1001.
   ──────────────────────────────────────────────── */
function ppProducts() {
  const day = 86400000;
  const iso = n => new Date(Date.now() + n * day).toISOString().slice(0, 10);
  return [
    { id: 1001, name: 'Coke Mismo',             variant: '295mL',     brand: 'Coca-Cola',    category: 'Beverages',         sku: 'PRD-1001', unit: 'pcs',    stock: 96,  reorder: 24, price: 20,   cost: 15,   expiry: iso(180) },
    { id: 1002, name: 'Royal Tru-Orange',       variant: '1L',        brand: 'Coca-Cola',    category: 'Beverages',         sku: 'PRD-1002', unit: 'pcs',    stock: 40,  reorder: 12, price: 45,   cost: 34,   expiry: iso(150) },
    { id: 1003, name: 'Purified Water',         variant: '500mL',     brand: 'Wilkins',      category: 'Beverages',         sku: 'PRD-1003', unit: 'pcs',    stock: 120, reorder: 36, price: 15,   cost: 9,    expiry: iso(400) },
    { id: 1004, name: 'Nescafé 3-in-1 Original', variant: '30g',      brand: 'Nestlé',       category: 'Beverages',         sku: 'PRD-1004', unit: 'sachet', stock: 200, reorder: 60, price: 9,    cost: 6.5,  expiry: iso(300) },
    { id: 1005, name: 'Piattos Cheese',         variant: '40g',       brand: "Jack 'n Jill", category: 'Snacks',            sku: 'PRD-1005', unit: 'pack',   stock: 60,  reorder: 20, price: 18,   cost: 13.5, expiry: iso(90)  },
    { id: 1006, name: 'Prawn Crackers',         variant: '60g',       brand: 'Oishi',        category: 'Snacks',            sku: 'PRD-1006', unit: 'pack',   stock: 8,   reorder: 15, price: 16,   cost: 11.5, expiry: iso(70)  },
    { id: 1007, name: 'Skyflakes Crackers',     variant: '10 packs',  brand: 'Monde',        category: 'Snacks',            sku: 'PRD-1007', unit: 'pack',   stock: 45,  reorder: 15, price: 62,   cost: 48,   expiry: iso(200) },
    { id: 1008, name: 'Pancit Canton Chilimansi', variant: '60g',     brand: 'Lucky Me!',    category: 'Canned & Instant',  sku: 'PRD-1008', unit: 'pack',   stock: 150, reorder: 50, price: 16,   cost: 12,   expiry: iso(240) },
    { id: 1009, name: 'Corned Beef',            variant: '150g',      brand: 'Argentina',    category: 'Canned & Instant',  sku: 'PRD-1009', unit: 'can',    stock: 36,  reorder: 12, price: 42,   cost: 33,   expiry: iso(500) },
    { id: 1010, name: 'Sardines in Tomato Sauce', variant: '155g',    brand: 'Ligo',         category: 'Canned & Instant',  sku: 'PRD-1010', unit: 'can',    stock: 0,   reorder: 24, price: 24,   cost: 18,   expiry: iso(450) },
    { id: 1011, name: 'Well-milled Rice',       variant: '1kg',       brand: '',             category: 'Pantry',            sku: 'PRD-1011', unit: 'kg',     stock: 180, reorder: 50, price: 58,   cost: 50,   expiry: null     },
    { id: 1012, name: 'Cooking Oil',            variant: '1L',        brand: 'Baguio Oil',   category: 'Pantry',            sku: 'PRD-1012', unit: 'pcs',    stock: 24,  reorder: 10, price: 118,  cost: 105,  expiry: iso(320) },
    { id: 1013, name: 'White Sugar',            variant: '1kg',       brand: '',             category: 'Pantry',            sku: 'PRD-1013', unit: 'kg',     stock: 70,  reorder: 20, price: 82,   cost: 72,   expiry: null     },
    { id: 1014, name: 'Tide Powder',            variant: '66g',       brand: 'P&G',          category: 'Household',         sku: 'PRD-1014', unit: 'sachet', stock: 90,  reorder: 30, price: 12,   cost: 9,    expiry: null     },
    { id: 1015, name: 'Dishwashing Liquid',     variant: '250mL',     brand: 'Joy',          category: 'Household',         sku: 'PRD-1015', unit: 'pcs',    stock: 25,  reorder: 10, price: 48,   cost: 37,   expiry: null     },
    { id: 1016, name: 'Bleach',                 variant: '250mL',     brand: 'Zonrox',       category: 'Household',         sku: 'PRD-1016', unit: 'pcs',    stock: 30,  reorder: 10, price: 28,   cost: 21,   expiry: null     },
    { id: 1017, name: 'Bath Soap',              variant: '85g',       brand: 'Safeguard',    category: 'Personal Care',     sku: 'PRD-1017', unit: 'bar',    stock: 60,  reorder: 20, price: 38,   cost: 29,   expiry: null     },
    { id: 1018, name: 'Shampoo',                variant: '12mL',      brand: 'Head & Shoulders', category: 'Personal Care', sku: 'PRD-1018', unit: 'sachet', stock: 140, reorder: 40, price: 9,    cost: 6.8,  expiry: null     },
    { id: 1019, name: 'Toothpaste',             variant: '50g',       brand: 'Colgate',      category: 'Personal Care',     sku: 'PRD-1019', unit: 'tube',   stock: 22,  reorder: 12, price: 35,   cost: 27,   expiry: null     },
    { id: 1020, name: 'Eggs',                   variant: 'Medium',    brand: '',             category: 'Fresh & Frozen',    sku: 'PRD-1020', unit: 'pcs',    stock: 120, reorder: 30, price: 9,    cost: 7.5,  expiry: iso(14)  },
    { id: 1021, name: 'Fresh Milk',             variant: '1L',        brand: 'Selecta',      category: 'Fresh & Frozen',    sku: 'PRD-1021', unit: 'pcs',    stock: 14,  reorder: 6,  price: 98,   cost: 80,   expiry: iso(20)  },
    { id: 1022, name: 'Tender Juicy Hotdog',    variant: '250g',      brand: 'Purefoods',    category: 'Fresh & Frozen',    sku: 'PRD-1022', unit: 'pack',   stock: 18,  reorder: 8,  price: 85,   cost: 68,   expiry: iso(-3)  },
    { id: 1023, name: 'Ballpen (Black)',        variant: '0.5mm',     brand: 'HBW',          category: 'School & Office',   sku: 'PRD-1023', unit: 'pcs',    stock: 100, reorder: 30, price: 10,   cost: 6,    expiry: null     },
    { id: 1024, name: 'Notebook',               variant: '80 leaves', brand: 'Cattleya',     category: 'School & Office',   sku: 'PRD-1024', unit: 'pcs',    stock: 40,  reorder: 15, price: 32,   cost: 24,   expiry: null     },
  ];
}


/* ------------------------------------------------
   Sample sales for the general-store catalog (ppProducts).
   Sale lines keep brand/strength/generic/rx next to name/variant so the
   Returns and Reports pages can still read them.
   ------------------------------------------------ */
function ppSampleStoreSales() {
  const items = ppProducts(), byId = id => items.find(m => m.id === id);
  const round = n => Math.round(n * 100) / 100;
  // [daysAgo, hour, minute, [[productId, qty], ...], method, discount, 'void'?]
  const spec = [
    [0, 8, 40,  [[1001, 2], [1005, 1]],             'cash',  'none'],   [0, 10, 15, [[1008, 3], [1020, 6]],            'gcash', 'none'],
    [0, 12, 30, [[1011, 2], [1012, 1], [1013, 1]],  'cash',  'none'],   [0, 15, 5,  [[1017, 2], [1019, 1]],            'card',  'none'],
    [1, 9, 50,  [[1011, 3], [1009, 2]],             'cash',  'senior'], [1, 14, 20, [[1003, 6], [1006, 4]],            'gcash', 'none'],
    [2, 11, 10, [[1023, 5], [1024, 3]],             'cash',  'none'],   [3, 16, 35, [[1014, 4], [1015, 1], [1016, 1]], 'cash',  'none'],
    [4, 10, 0,  [[1021, 2]],                        'card',  'none'],   [6, 13, 45, [[1002, 2], [1007, 1]],            'gcash', 'none', 'void'],
    [9, 9, 25,  [[1011, 5], [1018, 10]],            'cash',  'pwd'],    [12, 17, 10, [[1008, 10]],                     'card',  'none'],
    [20, 11, 0, [[1004, 12]],                       'cash',  'none'],   [35, 10, 30, [[1012, 2], [1013, 2]],           'cash',  'none'],
  ];
  const counters = {}, out = [];
  spec.slice().sort((a, b) => (b[0] - a[0]) || (a[1] - b[1]) || (a[2] - b[2])).forEach((s, i) => {
    const d = new Date(); d.setDate(d.getDate() - s[0]); d.setHours(s[1], s[2], 0, 0);
    const day = d.toISOString().slice(0, 10).replace(/-/g, '');
    counters[day] = (counters[day] || 0) + 1;
    const lines = s[3].map(([id, qty]) => { const m = byId(id); return { m: { id: m.id, name: m.name, variant: m.variant, brand: m.name, strength: m.variant, generic: m.brand || '', rx: false, price: m.price }, qty, total: m.price * qty }; });
    const gross = lines.reduce((t, l) => t + l.total, 0), base = gross / 1.12, vat = gross - base;
    const t = s[5] === 'none' ? { gross, vat, disc: 0, total: gross } : { gross, vat, disc: base * 0.2, total: round(base * 0.8) };
    const total = round(t.total), cash = s[4] === 'cash';
    const tendered = cash ? Math.ceil(total / 50) * 50 : total;
    const sale = {
      no: 'R-' + day + '-' + String(counters[day]).padStart(4, '0'), ts: d.toISOString(),
      date: d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }),
      cashier: 'Maria Santos', status: 'completed', lines, t: { ...t, total }, discount: s[5],
      method: s[4], tendered, change: cash ? round(tendered - total) : 0, ref: cash ? '' : 'REF' + (100000 + i * 7919),
      customer: s[5] === 'none' ? null : { type: s[5] === 'senior' ? 'Senior' : 'PWD', name: s[5] === 'senior' ? 'Remedios Cruz' : 'Lito Bautista', id: (s[5] === 'senior' ? 'SC-' : 'PWD-') + (20000 + i * 31) },
      rx: null,
    };
    if (s[6] === 'void') { sale.status = 'voided'; sale.voidReason = 'Customer changed their mind'; sale.voidedAt = new Date(d.getTime() + 600000).toISOString(); }
    out.push(sale);
  });
  return out.sort((a, b) => b.ts.localeCompare(a.ts));
}
