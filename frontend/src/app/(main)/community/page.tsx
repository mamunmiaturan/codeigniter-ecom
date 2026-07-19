"use client";

import { motion } from "framer-motion";
import { Heart, MessageCircle, Share2 } from "lucide-react";

const spaces = [
  {
    id: 1,
    name: "Proud Maa Circle",
    nameBn: "গর্বিত মা সার্কেল",
    emoji: "👑",
    bg: "#faf6f0",
    accent: "#E8470A",
    members: "2.2K",
    description:
      "Celebrate every milestone of your Sona. Share achievements, milestones & proud moments.",
    posts: [
      {
        author: "Reshma A.",
        avatar: "RA",
        time: "2h",
        content: "আজ আমার সোনা প্রথমবার স্কুলে গেল! 🥹❤️",
        likes: 1200,
        comments: 86,
      },
      {
        author: "Mim S.",
        avatar: "MS",
        time: "4h",
        content: "Report card day! সোনা ক্লাসে প্রথম হয়েছে 🌟",
        likes: 980,
        comments: 64,
      },
    ],
  },
  {
    id: 2,
    name: "Babu Roast Club",
    nameBn: "বাবু রোস্ট ক্লাব",
    emoji: "😄",
    bg: "#faf6f0",
    accent: "#0A0A3E",
    members: "1.6K",
    description:
      "Funny husband stories. We all have them. Share & laugh together!",
    posts: [
      {
        author: "Nusrat J.",
        avatar: "NJ",
        time: "3h",
        content: "বাবু বলল বাজার করবে, ৩ ঘন্টা পরে খালি হাতে ফিরল 😭",
        likes: 987,
        comments: 124,
      },
      {
        author: "Priya M.",
        avatar: "PM",
        time: "5h",
        content:
          "বাবু ফোন রেখেছে কোথায় মনে নেই, কিন্তু ক্রিকেট স্কোর মনে আছে 😐",
        likes: 756,
        comments: 89,
      },
    ],
  },
  {
    id: 3,
    name: "My Sona Diaries",
    nameBn: "সোনার ডায়েরি",
    emoji: "⭐",
    bg: "#faf6f0",
    accent: "#E8470A",
    members: "3.1K",
    description:
      "A journal of your little one's journey. First steps, first words, first day of school.",
    posts: [
      {
        author: "Sabrina K.",
        avatar: "SK",
        time: "5h",
        content: "সোনার প্রথম শব্দ 'মামা' — আমি কাঁদলাম 😭❤️",
        likes: 1500,
        comments: 92,
      },
      {
        author: "Tania R.",
        avatar: "TR",
        time: "8h",
        content: "আজ সোনা নিজে জুতা পরল! এত বড় হয়ে গেল 🥹",
        likes: 890,
        comments: 67,
      },
    ],
  },
  {
    id: 4,
    name: "Ask BabuSona",
    nameBn: "জিজ্ঞেস করুন",
    emoji: "🙋‍♀️",
    bg: "#faf6f0",
    accent: "#0A0A3E",
    members: "880",
    description:
      "Real questions. Real answers. Expert support from our community.",
    posts: [
      {
        author: "Tania A.",
        avatar: "TA",
        time: "7h",
        content: "সোনার বয়স ৪, খেতে চায় না। কেউ টিপস দেবেন?",
        likes: 680,
        comments: 53,
      },
      {
        author: "Rima B.",
        avatar: "RB",
        time: "10h",
        content: "বাবুর জন্য কোন গিফট ভালো হবে anniversary তে?",
        likes: 450,
        comments: 78,
      },
    ],
  },
];

export default function CommunityPage() {
  return (
    <div style={{ background: "#f7f0e6", minHeight: "100vh" }}>
      {/* Hero */}
      <div
        style={{
          background: "linear-gradient(135deg, #faf6f0 0%, #f7f0e6 100%)",
          padding: "80px clamp(1.5rem, 4vw, 5rem)",
          textAlign: "center",
          borderBottom: "1px solid #e8ddd5",
        }}
      >
        <div style={{ maxWidth: "700px", margin: "0 auto" }}>
          <motion.p
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "10px",
              fontWeight: "700",
              letterSpacing: "4px",
              textTransform: "uppercase",
              color: "#E8470A",
              marginBottom: "16px",
            }}
          >
            Community
          </motion.p>

          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            style={{
              fontFamily: "var(--font-playfair)",
              fontSize: "clamp(2rem, 4vw, 3rem)",
              fontWeight: "700",
              color: "#0A0A3E",
              lineHeight: "1.3",
              marginBottom: "16px",
              letterSpacing: "-0.5px",
            }}
          >
            BabuSona Community <span style={{ color: "#E8470A" }}>♡</span>
          </motion.h1>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            style={{
              fontFamily: "var(--font-hind)",
              fontSize: "16px",
              color: "#0A0A3E",
              fontWeight: "600",
              marginBottom: "8px",
            }}
          >
            এখানে আমরা এক অপরের মানুষ।
          </motion.p>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            style={{
              fontFamily: "var(--font-dm-sans)",
              fontSize: "14px",
              color: "#7a6e65",
              marginBottom: "40px",
              lineHeight: "1.8",
            }}
          >
            A safe, fun & supportive space for women who live for their
            BabuSona.
          </motion.p>

          {/* Stats */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            style={{
              display: "flex",
              justifyContent: "center",
              gap: "48px",
              flexWrap: "wrap",
            }}
          >
            {[
              { value: "10K+", label: "Members" },
              { value: "4", label: "Community Spaces" },
              { value: "500+", label: "Daily Posts" },
            ].map((stat, i) => (
              <div key={i} style={{ textAlign: "center" }}>
                <p
                  style={{
                    fontFamily: "var(--font-playfair)",
                    fontSize: "2.2rem",
                    fontWeight: "700",
                    color: "#E8470A",
                    lineHeight: 1,
                  }}
                >
                  {stat.value}
                </p>
                <p
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    color: "#7a6e65",
                    marginTop: "4px",
                    letterSpacing: "1px",
                    textTransform: "uppercase",
                  }}
                >
                  {stat.label}
                </p>
              </div>
            ))}
          </motion.div>
        </div>
      </div>

      {/* Community Spaces */}
      <div
        style={{
          maxWidth: "1300px",
          margin: "0 auto",
          padding: "64px clamp(1.5rem, 4vw, 5rem)",
          display: "flex",
          flexDirection: "column",
          gap: "32px",
        }}
      >
        {spaces.map((space, i) => (
          <motion.div
            key={space.id}
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.1 }}
            style={{
              background: "white",
              borderRadius: "24px",
              border: "1px solid #e8ddd5",
              overflow: "hidden",
              boxShadow: "0 4px 24px rgba(10,10,62,0.06)",
            }}
          >
            {/* Space Header */}
            <div
              style={{
                background: space.bg,
                padding: "24px 32px",
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                flexWrap: "wrap",
                gap: "16px",
                borderBottom: "1px solid #e8ddd5",
              }}
            >
              <div
                style={{ display: "flex", alignItems: "center", gap: "16px" }}
              >
                <div
                  style={{
                    width: "56px",
                    height: "56px",
                    background: "white",
                    borderRadius: "16px",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    fontSize: "1.8rem",
                    boxShadow: "0 4px 16px rgba(10,10,62,0.08)",
                    border: "1px solid #e8ddd5",
                  }}
                >
                  {space.emoji}
                </div>
                <div>
                  <h3
                    style={{
                      fontFamily: "var(--font-playfair)",
                      fontSize: "1.2rem",
                      fontWeight: "700",
                      color: "#0A0A3E",
                      marginBottom: "2px",
                    }}
                  >
                    {space.name}
                  </h3>
                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "12px",
                      color: space.accent,
                      fontWeight: "600",
                    }}
                  >
                    {space.nameBn}
                  </p>
                </div>
              </div>
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: "16px",
                }}
              >
                <span
                  style={{
                    fontFamily: "var(--font-dm-sans)",
                    fontSize: "11px",
                    color: "#7a6e65",
                    letterSpacing: "0.5px",
                  }}
                >
                  {space.members} members
                </span>
                <button
                  style={{
                    background: space.accent,
                    color: "white",
                    border: "none",
                    borderRadius: "25px",
                    padding: "10px 20px",
                    fontSize: "11px",
                    fontWeight: "700",
                    cursor: "pointer",
                    fontFamily: "var(--font-dm-sans)",
                    letterSpacing: "1px",
                    textTransform: "uppercase",
                    boxShadow: `0 4px 16px ${space.accent}30`,
                  }}
                >
                  Join Space
                </button>
              </div>
            </div>

            {/* Description */}
            <div style={{ padding: "20px 32px 0" }}>
              <p
                style={{
                  fontFamily: "var(--font-dm-sans)",
                  fontSize: "13px",
                  color: "#7a6e65",
                  lineHeight: "1.7",
                }}
              >
                {space.description}
              </p>
            </div>

            {/* Posts */}
            <div
              style={{
                padding: "20px 32px 28px",
                display: "grid",
                gridTemplateColumns: "1fr 1fr",
                gap: "16px",
              }}
            >
              {space.posts.map((post, j) => (
                <div
                  key={j}
                  style={{
                    background: "#faf6f0",
                    borderRadius: "16px",
                    padding: "16px",
                    border: "1px solid #e8ddd5",
                  }}
                >
                  <div
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: "10px",
                      marginBottom: "10px",
                    }}
                  >
                    <div
                      style={{
                        width: "34px",
                        height: "34px",
                        borderRadius: "50%",
                        background: `linear-gradient(135deg, #0A0A3E, #E8470A)`,
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        fontSize: "11px",
                        fontWeight: "700",
                        color: "white",
                        flexShrink: 0,
                        fontFamily: "var(--font-dm-sans)",
                      }}
                    >
                      {post.avatar}
                    </div>
                    <div style={{ flex: 1 }}>
                      <p
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "12px",
                          fontWeight: "700",
                          color: "#0A0A3E",
                        }}
                      >
                        {post.author}
                      </p>
                      <p
                        style={{
                          fontFamily: "var(--font-dm-sans)",
                          fontSize: "10px",
                          color: "#c9a84c",
                        }}
                      >
                        {post.time}
                      </p>
                    </div>
                  </div>

                  <p
                    style={{
                      fontFamily: "var(--font-hind)",
                      fontSize: "13px",
                      color: "#444",
                      lineHeight: "1.6",
                      marginBottom: "12px",
                    }}
                  >
                    {post.content}
                  </p>

                  <div
                    style={{
                      display: "flex",
                      gap: "16px",
                      paddingTop: "10px",
                      borderTop: "1px solid #e8ddd5",
                    }}
                  >
                    <button
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: "4px",
                        background: "transparent",
                        border: "none",
                        cursor: "pointer",
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "11px",
                        color: "#7a6e65",
                      }}
                    >
                      <Heart size={12} color="#E8470A" fill="#E8470A" />
                      {post.likes >= 1000
                        ? `${(post.likes / 1000).toFixed(1)}K`
                        : post.likes}
                    </button>
                    <button
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: "4px",
                        background: "transparent",
                        border: "none",
                        cursor: "pointer",
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "11px",
                        color: "#7a6e65",
                      }}
                    >
                      <MessageCircle size={12} color="#7a6e65" />
                      {post.comments}
                    </button>
                    <button
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: "4px",
                        background: "transparent",
                        border: "none",
                        cursor: "pointer",
                        fontFamily: "var(--font-dm-sans)",
                        fontSize: "11px",
                        color: "#7a6e65",
                        marginLeft: "auto",
                      }}
                    >
                      <Share2 size={12} color="#7a6e65" />
                      Share
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </motion.div>
        ))}
      </div>

      <style>{`
        @media (max-width: 640px) {
          div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
          }
        }
      `}</style>
    </div>
  );
}
