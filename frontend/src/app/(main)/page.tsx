import { HeroSection } from "@/components/home/HeroSection";
import { BrandStorySection } from "@/components/home/BrandStorySection";
import { BrandsSection } from "@/components/home/BrandsSection";
import { BestsellersSection } from "@/components/home/BestsellersSection";
import { MissionSection } from "@/components/home/MissionSection";
import { CollectionsSection } from "@/components/home/CollectionsSection";
import { BespokeSection } from "@/components/home/BespokeSection";
import { CommunitySection } from "@/components/home/CommunitySection";
import { NewsletterSection } from "@/components/home/NewsletterSection";

export default function HomePage() {
  return (
    <div
      style={{
        background:
          "linear-gradient(180deg, #faf6f0 0%, #f7f0e6 30%, #f5ede6 70%, #f7ede8 100%)",
        minHeight: "100vh",
      }}
    >
      <HeroSection />
      <BrandStorySection />
      <BrandsSection />
      <BestsellersSection />
      <MissionSection />
      <CollectionsSection />
      <BespokeSection />
      <CommunitySection />
      <NewsletterSection />
    </div>
  );
}
