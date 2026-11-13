        /* Admin colour tokens — light by default, dark under data-theme="dark" (same palette as the website). */
        :root {
            --brand-navy: #032C3A; --navy: #032C3A; --navy-light: #0c4257; --sidebar: #032C3A; --cyan: #1FF2FA;
            --teal: #0CA7B5; --teal-hover: #098893; --teal-dark: #098893; --teal-soft: #E3FAFB;
            --bg: #F3F8FA; --card: #fff; --border: #E3ECEF; --text: #032C3A; --muted: #7E97A0;
            --input-bg: #fff; --soft-bg: #E7EFF1; --soft-bg-hover: #d9e5e8;
            --danger: #dc2626; --danger-dark: #b91c1c; --danger-soft: #fee2e2; --danger-text: #991b1b; --danger-border: #fecaca;
            --success-soft: #dcfce7; --success-text: #166534; --success-border: #bbf7d0;
            --warn-soft: #fef3c7; --warn-text: #92400e; --indigo-soft: #e0e7ff; --indigo-text: #3730a3;
            --gray-soft: #f3f4f6; --gray-text: #4b5563;
            --radius: 16px; --radius-sm: 10px;
            --shadow: 0 2px 14px rgba(3,44,58,.07); --shadow-lg: 0 14px 34px rgba(3,44,58,.14);
            --ease: cubic-bezier(.2,.7,.2,1);
        }
        :root[data-theme="dark"] {
            --navy: #E8F4F7; --navy-light: #0f3646; --sidebar: #031820; --teal-dark: #2CC5D2; --teal-soft: rgba(12,167,181,.16);
            --bg: #061920; --card: #0B2530; --border: #17394A; --text: #DCEBEF; --muted: #86A3AD;
            --input-bg: #08202A; --soft-bg: #133443; --soft-bg-hover: #1a4152;
            --danger-soft: rgba(220,38,38,.16); --danger-text: #fca5a5; --danger-border: rgba(248,113,113,.3);
            --success-soft: rgba(22,163,74,.16); --success-text: #86efac; --success-border: rgba(134,239,172,.3);
            --warn-soft: rgba(217,119,6,.18); --warn-text: #fcd34d; --indigo-soft: rgba(99,102,241,.2); --indigo-text: #a5b4fc;
            --gray-soft: rgba(148,163,184,.15); --gray-text: #cbd5e1;
            --shadow: 0 2px 14px rgba(0,0,0,.35); --shadow-lg: 0 14px 34px rgba(0,0,0,.5);
        }
